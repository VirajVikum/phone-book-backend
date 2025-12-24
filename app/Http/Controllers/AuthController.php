<?php

namespace App\Http\Controllers;

use App\Models\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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

    public function sendTelegramOtp(Request $request)
{
    $phone = $request->mobile; // e.g., "0771234567"
    $username = $request->telegram_username ?? null; // Optional fallback

    // Format phone
    $phoneIntl = preg_replace('/\D/', '', $phone);
    if (strlen($phoneIntl) == 10) $phoneIntl = '94' . substr($phoneIntl, 1);
    $phoneIntl = '+' . $phoneIntl;

    $otp = rand(100000, 999999);
    $key = $username ?? $phoneIntl;
    Cache::put('tg_otp_' . $key, $otp, now()->addMinutes(5));

    $message = "🔐 Phone Book Sri Lanka\n\nYour code: *$otp*\nExpires in 5 min.";

    // Try phone first
    if ($phoneIntl) {
        $getChat = Http::get("https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN') . "/getChat", [
            'phone_number' => $phoneIntl
        ]);

        if ($getChat->successful() && isset($getChat->json()['result']['id'])) {
            $chatId = $getChat->json()['result']['id'];
            $send = Http::post("https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN') . "/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown'
            ]);

            if ($send->successful()) return response()->json(['success' => true]);
        }
    }

    // Fallback to username
    if ($username) {
        $getChat = Http::get("https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN') . "/getChat", [
            'chat_id' => $username
        ]);

        if ($getChat->successful() && isset($getChat->json()['result']['id'])) {
            $chatId = $getChat->json()['result']['id'];
            $send = Http::post("https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN') . "/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown'
            ]);

            if ($send->successful()) return response()->json(['success' => true]);
        }
    }

    return response()->json([
        'success' => false,
        'message' => 'User must start bot chat first. Send /start to @your_bot'
    ], 400);
}

public function verifyTelegramOtp(Request $request)
{
    $username = ltrim($request->telegram_username, '@');
    $otp = $request->otp;

    $cachedOtp = Cache::get('tg_otp_' . $username);

    if ($cachedOtp && (string)$cachedOtp === (string)$otp) {
        Cache::forget('tg_otp_' . $username);
        return response()->json(['success' => true, 'message' => 'Telegram verified!']);
    }

    return response()->json(['success' => false, 'message' => 'Invalid or expired OTP'], 400);
}





// public function sendOwnWhatsAppOtp(Request $request)
// {
//     $mobile10 = $request->mobile; // 0771234567
//     $mobile94 = '94' . ltrim($mobile10, '0'); // 94771234567

//     $otp = rand(100000, 999999);
//     Cache::put('wa_otp_' . $mobile94, $otp, now()->addMinutes(5));

//     $response = Http::post('http://127.0.0.1:3000/send-otp', [
//         'number' => $mobile94,
//         'otp' => $otp
//     ]);

//     if ($response->successful() && $response->json('success')) {
//         return response()->json(['success' => true]);
//     }

//     return response()->json(['success' => false, 'message' => 'Failed to send'], 500);
// }


public function sendOwnWhatsAppOtp(Request $request)
{
    $mobile10 = $request->mobile;

    if (!preg_match('/^0[7-9][0-9]{8}$/', $mobile10)) {
        return response()->json(['success' => false, 'message' => 'Invalid mobile'], 400);
    }

    $mobile94 = '94' . ltrim($mobile10, '0');

    // Let Node.js generate and send the OTP
    $response = Http::timeout(20)->post('https://pb-otp-system-production.up.railway.app:3000/send-otp', [
        'number' => $mobile94,
        'otp'    => '' // Let Node.js generate it
    ]);

    \Log::info('WhatsApp Node Response', ['body' => $response->body()]);

    if ($response->successful() && $response->json('success')) {
        $realOtp = $response->json('otp'); // GET THE OTP THAT WAS ACTUALLY SENT

        Cache::put('wa_otp_' . $mobile94, $realOtp, now()->addMinutes(5));

        return response()->json(['success' => true]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Failed to send OTP',
        'debug' => $response->body()
    ], 500);
}

public function verifyWhatsAppOtp(Request $request)
{
    $mobile10 = $request->mobile;
    $mobile94 = '94' . ltrim($mobile10, '0');
    $otp = $request->otp;

    $cached = Cache::get('wa_otp_' . $mobile94);

    if ($cached && (string)$cached === (string)$otp) {
        Cache::forget('wa_otp_' . $mobile94);
        return response()->json(['success' => true, 'message' => 'Verified']); // ← 200 OK
    }

    // NEVER return 400 — Flutter can't read it properly
    return response()->json(['success' => false, 'message' => 'Wrong OTP'], 200);
    //                                     ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
}


public function loginWithWhatsApp(Request $request)
{
    $request->validate([
        'mobile' => 'required|string|size:10', // 0771234567 format
    ]);

    $mobile = $request->mobile;

    // Find provider by mobile_no OR whatsapp_no
    $provider = ServiceProvider::where('whatsapp_no', $mobile)
                ->first();

    if (!$provider) {
        return response()->json([
            'success' => false,
            'message' => 'No account found with this number'
        ], 404);
    }

    // Generate token — SAME AS REGISTRATION
    $token = $provider->createToken('auth_token')->plainTextToken;

    // Load relationships
    $provider->load('towns');

    return response()->json([
        'success'  => true,
        'message'  => 'Login successful',
        'token'    => $token,
        'provider' => $provider
    ], 200);
}


}