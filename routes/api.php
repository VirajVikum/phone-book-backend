<?php

use App\Http\Controllers\Api\DistrictController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\AuthController;

Route::get('/districts', [DistrictController::class, 'index']);
Route::get('/districts/{id}/towns', [DistrictController::class, 'towns']);
Route::post('/register_provider', [ProviderController::class, 'register']);
Route::get('/search_providers', [ProviderController::class, 'search']);

Route::post('/send-email-otp', [AuthController::class, 'sendEmailOtp']);
Route::post('/verify-email-otp', [AuthController::class, 'verifyEmailOtp']);