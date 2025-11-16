<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    public function index() {
        return response()->json(District::all());
    }

    public function towns($id) {
        return response()->json(District::findOrFail($id)->towns);
    }
}
