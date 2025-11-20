<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    public function sendEmailOtp(Request $request)
    {
        $email = strtolower($request->email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'message' => 'Invalid email'], 400);
        }

        // Optional: force Gmail only
        if (!str_ends_with($email, '@gmail.com')) {
            return response()->json(['success' => false, 'message' => 'Only Gmail allowed'], 400);
        }

        $otp = rand(100000, 999999);
        Cache::put('otp_' . $email, $otp, now()->addMinutes(5));

        try {
            Mail::raw("Your Phone Book verification code is: $otp\n\nValid for 5 minutes.", function ($message) use ($email) {
                $message->to($email)
                        ->subject('Your Verification Code');
            });

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Mail failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send email'], 500);
        }
    }

    public function verifyEmailOtp(Request $request)
    {
        $email = strtolower($request->email);
        $otp = $request->otp;

        $cached = Cache::get('otp_' . $email);

        if ($cached && $cached == $otp) {
            Cache::forget('otp_' . $email);
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid OTP'], 400);
    }
}