<?php

use App\Http\Controllers\Api\DistrictController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\AuthController;

Route::get('/districts', [DistrictController::class, 'index']);
Route::get('/districts/{id}/towns', [DistrictController::class, 'towns']);
Route::post('/register_provider', [ProviderController::class, 'register']);
Route::get('/search_providers', [ProviderController::class, 'search']);
Route::put('/update_provider', [ProviderController::class, 'update']);

Route::post('/send-email-otp', [AuthController::class, 'sendEmailOtp']);
Route::post('/verify-email-otp', [AuthController::class, 'verifyEmailOtp']);

Route::post('/send-telegram-otp', [AuthController::class, 'sendTelegramOtp']);
Route::post('/verify-telegram-otp', [AuthController::class, 'verifyTelegramOtp']);

Route::post('/send-whatsapp-otp-own', [AuthController::class, 'sendOwnWhatsAppOtp']);
Route::post('/verify-whatsapp-otp', [AuthController::class, 'verifyWhatsAppOtp']);