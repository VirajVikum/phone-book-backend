<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GnDivision;
use Illuminate\Http\Request;

class GnDivisionController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('query');
        
        if (empty($query)) {
            return response()->json([]);
        }

        $results = GnDivision::where('name', 'LIKE', "%{$query}%")
            ->with('district')
            ->limit(15)
            ->get()
            ->map(function ($gn) {
                return [
                    'attributes' => [
                        'gnd_name' => $gn->name,
                        'ds_division_name' => $gn->ds_division_name,
                        'district_name' => $gn->district->name ?? 'Unknown',
                        'gn_code' => $gn->gn_code,
                    ],
                    'geometry' => [
                        'x' => (float) $gn->longitude,
                        'y' => (float) $gn->latitude,
                    ]
                ];
            });

        return response()->json($results);
    }
}
