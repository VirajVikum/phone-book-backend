<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ProviderController extends Controller
{
    public function register(Request $request)
    {
        try {
            // Validate required fields
            $request->validate([
                'name' => 'required|string',
                'gender' => 'required|string',
                'mobile_no' => 'required',
                'address' => 'required|string',
                'district_id' => 'required|integer',
                'towns' => 'required|array',
                'email' => 'required|email'
            ]);

            // Handle photo upload
            $photoPath = null;

            if ($request->hasFile('photo')) {

                $ext = $request->file('photo')->getClientOriginalExtension();

                // select whatsapp_no first, else mobile_no
                $idPart = $request->whatsapp_no ?? $request->mobile_no;

                // timestamp format e.g., 20250212_154422
                $timestamp = now()->format('Ymd_His');

                // final filename
                $uniqueName = "{$idPart}_{$timestamp}.{$ext}";

                // save file
                $photoPath = $request->file('photo')->storeAs(
                    'provider_photos',
                    $uniqueName,
                    'public'
                );
            }

            // Create provider entry
            $provider = ServiceProvider::create([
                'name' => $request->name,
                'gender' => $request->gender,
                'mobile_no' => $request->mobile_no,
                'whatsapp_no' => $request->whatsapp_no,
                'address' => $request->address,
                'district_id' => $request->district_id,
                'industries' => json_encode($request->industries),
                'photo_url' => $photoPath ? "/storage/$photoPath" : null,
                'email' => $request->email,
            ]);

            if ($request->has('towns')) {
                $provider->towns()->sync($request->towns);
            }
            $provider->load('towns');

            $token = $provider->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'provider' => $provider,
                'message' => 'Provider registered successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function search(Request $request)
{
    $request->validate([
        // 'district_id' => 'required|integer',
        'town_id' => 'required|integer',
        'requirement' => 'required|string'
    ]);

    // $districtId = $request->district_id;
    $townId = $request->town_id;
    $keyword = strtolower($request->requirement);

    $providers = ServiceProvider::with('towns')
        // ->where('district_id', $districtId)
        ->whereHas('towns', function ($q) use ($townId) {
            $q->where('town_id', $townId);
        })
        ->get()
        ->filter(function ($provider) use ($keyword) {
            $industries = $provider->industries ?? [];
            // $industries should be array (cast in model). If it's string, decode:
            if (is_string($industries)) {
                $industries = json_decode($industries, true) ?: [];
            }
            foreach ($industries as $ind) {
                if (stripos($ind, $keyword) === 0) { // starts with keyword
                    return true;
                }
            }
            return false;
        })->values();

    return response()->json($providers);
}

public function update(Request $request)
{
    try {
        // THIS IS THE CORRECT WAY TO GET AUTHENTICATED USER WITH SANCTUM
        $provider = auth('sanctum')->user();

        if (!$provider) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Validation (same as register)
        $request->validate([
            'name'        => 'required|string|max:255',
            'gender'      => 'required|in:M,F',
            'address'     => 'required|string',
            'district_id' => 'required|integer|exists:districts,id',
            'towns'       => 'required|array|min:1',
            'towns.*'     => 'integer|exists:towns,id',
            'industries'  => 'required|array|min:1',
            'industries.*'=> 'string|max:100',
            'photo'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Handle photo upload — EXACTLY SAME LOGIC AS REGISTER
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($provider->photo_url) {
                $oldPath = str_replace('/storage/', '', $provider->photo_url);
                Storage::disk('public')->delete($oldPath);
            }

            $ext = $request->file('photo')->getClientOriginalExtension();
            $idPart = $request->whatsapp_no ?? $provider->mobile_no;
            $timestamp = now()->format('Ymd_His');
            $uniqueName = "{$idPart}_{$timestamp}.{$ext}";

            $photoPath = $request->file('photo')->storeAs(
                'provider_photos',
                $uniqueName,
                'public'
            );

            $provider->photo_url = "/storage/{$photoPath}";
        }

        // Update all fields — EXACTLY LIKE REGISTER
        $provider->update([
            'name'        => $request->name,
            'gender'      => $request->gender,
            'whatsapp_no' => $request->whatsapp_no ?? $provider->whatsapp_no,
            'address'     => $request->address,
            'district_id' => $request->district_id,
            'industries'  => $request->industries, // auto json_encode because of $casts
        ]);

        // Sync towns
        $provider->towns()->sync($request->towns);
        $provider->load('towns');

        return response()->json([
            'success'  => true,
            'message'  => 'Profile updated successfully',
            'provider' => $provider
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Server error',
            'error'   => $e->getMessage()
        ], 500);
    }
}

public function deleteAccount(Request $request)
{
    // THIS IS THE FIX — GET USER FROM SANCTUM
    $provider = $request->user();

    // If still null (very rare), fallback to Auth
    if (!$provider) {
        $provider = Auth::guard('sanctum')->user();
    }

    if (!$provider) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    // Optional: Delete photo
    // if ($provider->photo_url) {
    //     $path = str_replace('/storage', 'public', $provider->photo_url);
    //     if (Storage::exists($path)) {
    //         Storage::delete($path);
    //     }
    // }

    // Revoke all tokens first
    // $provider->tokens()->delete();

    // SOFT DELETE
    $provider->delete();

    return response()->json([
        'success' => true,
        'message' => 'Your account has been deleted successfully.'
    ]);
}

}
