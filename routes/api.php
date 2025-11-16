<?php

use App\Http\Controllers\Api\DistrictController;
use App\Http\Controllers\Api\ProviderController;

Route::get('/districts', [DistrictController::class, 'index']);
Route::get('/districts/{id}/towns', [DistrictController::class, 'towns']);
Route::post('/register_provider', [ProviderController::class, 'register']);
Route::get('/search_providers', [ProviderController::class, 'search']);
