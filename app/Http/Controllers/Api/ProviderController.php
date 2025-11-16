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
        $provider = ServiceProvider::create([
            'name' => $request->name,
            'gender' => $request->gender,
            'mobile_no' => $request->mobile_no,
            'whatsapp_no' => $request->whatsapp_no,
            'address' => $request->address,
            'district_id' => $request->district_id,
            'industries' => $request->industries,
            'photo_url' => $request->photo_url ?? ''
        ]);

        foreach($request->towns as $townId){
            ProviderTown::create(['provider_id'=>$provider->id,'town_id'=>$townId]);
        }

        return response()->json(['success' => true, 'provider_id' => $provider->id]);
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
