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
                'district_id' => 'nullable|integer',
                'towns' => 'nullable|array',
                'email' => 'nullable|email',
                'service_area' => 'nullable|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric'
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
                'service_area' => $request->service_area,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
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
            'requirement' => 'required|string',
            'town_id' => 'nullable|integer',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'radius' => 'nullable|numeric'
        ]);

        $keyword = strtolower($request->requirement);
        $lat = $request->lat;
        $lng = $request->lng;
        $radius = $request->radius ?? 10; // Default 10km radius
        $townId = $request->town_id;

        $query = ServiceProvider::with('towns');

        if ($lat && $lng) {
            // Proximity search using Haversine formula
            $query->selectRaw("*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", [$lat, $lng, $lat])
                ->having('distance', '<=', $radius)
                ->orderBy('distance');
        } elseif ($townId) {
            $query->whereHas('towns', function ($q) use ($townId) {
                $q->where('town_id', $townId);
            });
        }

        $providers = $query->get()
            ->filter(function ($provider) use ($keyword) {
                $industries = $provider->industries ?? [];
                if (is_string($industries)) {
                    $industries = json_decode($industries, true) ?: [];
                }
                foreach ($industries as $ind) {
                    if (stripos($ind, $keyword) !== false) {
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
            'district_id' => 'nullable|integer|exists:districts,id',
            'towns'       => 'nullable|array',
            'towns.*'     => 'integer|exists:towns,id',
            'industries'  => 'required|array|min:1',
            'industries.*'=> 'string|max:100',
            'photo'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'service_area'=> 'nullable|string',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
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
            'industries'  => $request->industries,
            'service_area'=> $request->service_area ?? $provider->service_area,
            'latitude'    => $request->latitude ?? $provider->latitude,
            'longitude'   => $request->longitude ?? $provider->longitude,
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
