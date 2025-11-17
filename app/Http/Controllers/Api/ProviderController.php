<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceProvider;
use Illuminate\Http\Request;

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
            ]);

            if ($request->has('towns')) {
                $provider->towns()->sync($request->towns);
            }

            return response()->json([
                'success' => true,
                'provider_id' => $provider->id,
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
        'district_id' => 'required|integer',
        'town_id' => 'required|integer',
        'requirement' => 'required|string'
    ]);

    $districtId = $request->district_id;
    $townId = $request->town_id;
    $keyword = strtolower($request->requirement);

    $providers = ServiceProvider::with('towns')
        ->where('district_id', $districtId)
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


}
