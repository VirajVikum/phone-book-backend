<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GnDivision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GnDivisionController extends Controller
{
    public function all()
    {
        $divisions = DB::table('gn_divisions')
            ->join('districts', 'gn_divisions.district_id', '=', 'districts.id')
            ->select(
                'gn_divisions.id',
                'gn_divisions.name',
                'gn_divisions.gn_code',
                'gn_divisions.ds_division_name',
                'gn_divisions.province_name',
                'gn_divisions.latitude',
                'gn_divisions.longitude',
                'gn_divisions.district_id',
                'districts.name as district_name'
            )
            ->get();

        return response()->json($divisions);
    }

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
