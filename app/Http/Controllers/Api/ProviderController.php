<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProviderTown;
use App\Models\ServiceProvider;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function register(Request $request)
{
    try {
        

        $provider = ServiceProvider::create([
            'name' => $request['name'],
            'gender' => $request['gender'],
            'mobile_no' => $request['mobile_no'],
            'whatsapp_no' => $request['whatsapp_no'],
            'address' => $request['address'],
            'district_id' => $request['district_id'],
            'industries' => $request['industries'],
            'photo_url' => $request['photo_url'] ?? null,
        ]);

        if ($request->has('towns')) {
            $provider->towns()->sync($request->towns);
        }

        return response()->json([
            'success' => true,
            'provider_id' => $provider->id,
            'message' => 'Provider registered successfully'
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'errors' => $e->errors()
        ], 422);
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
        $query = ServiceProvider::where('district_id', $request->district_id)
            ->whereJsonContains('industries', $request->requirement)
            ->whereHas('towns', fn($q) => $q->where('town_id', $request->town_id))
            ->get();

        return response()->json($query);
    }
}
