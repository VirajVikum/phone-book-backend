Purpose
-------
This file gives an AI coding agent the minimal, concrete knowledge to be productive in this repository: a Laravel API backend with a small Node service used for WhatsApp OTPs. Focus on measurable, discoverable patterns, run commands, and gotchas found in the code.

Quick Start (local)
- **Install & setup:** `composer run-script setup` (runs `composer install`, copies `.env`, generates key, migrates) then `npm install` if needed.
- **Run full dev stack:** `composer run-script dev` (runs `php artisan serve`, queue listener, pail logger, `npm run dev`).
- **Start only WhatsApp OTP server:** `npm run whatsapp` or `npm start` (runs `node server.js`, binds to `0.0.0.0:3000`). See [server.js](server.js#L1-L80).
- **Run tests:** `composer run-script test` (clears config and runs `php artisan test`).

Architecture (high level)
- Laravel API (REST) is the primary application: controllers live under [app/Http/Controllers](app/Http/Controllers) and API routes are in [routes/api.php](routes/api.php#L1-L40).
- Data models use Eloquent in [app/Models](app/Models). `ServiceProvider` uses `HasApiTokens` (Sanctum) and a global scope to hide soft-deleted rows.
- A small Node service (`server.js`) provides WhatsApp OTP sending via `whatsapp-web.js`. The Laravel backend may POST to that service (see `AuthController::sendOwnWhatsAppOtp`).

Project-specific conventions & patterns
- OTP keys in cache:
  - Email OTP: `otp_{email}` (see `AuthController::sendEmailOtp`).
  - Telegram OTP: `tg_otp_{username_or_phone}`.
  - WhatsApp OTP: `wa_otp_{94mobile}` — keys are stored using the `94` international prefix.
- Mobile format: the app expects a 10-digit local format like `0771234567`. Convert to international by replacing leading `0` with `94` (e.g., `94{ltrim(mobile,'0')}`). See `AuthController`.
- WhatsApp OTP flow: Laravel can either call a local `server.js` or an externally hosted OTP endpoint (the code currently calls a Railway URL). Node will return the actual OTP in its JSON response; Laravel caches that returned OTP under `wa_otp_{94mobile}`.
- Photo upload naming: photos are stored with the pattern `{idPart}_{Ymd_His}.{ext}` under the `public` disk and exposed via `/storage/{path}` (see `ProviderController::register` and `update`). Remember to run `php artisan storage:link` when serving uploaded files.
- Authentication: uses Laravel Sanctum tokens created with `$provider->createToken('auth_token')->plainTextToken`. Controllers retrieve the authenticated provider via `auth('sanctum')->user()` or `$request->user()`.
- Soft deletes & global scope: `ServiceProvider` applies a global scope to hide `deleted_at` rows — be mindful when writing queries or seeding data.

Important environment variables
- `TELEGRAM_BOT_TOKEN` — used by `AuthController::sendTelegramOtp` to call Telegram API.
- Mail settings — emails use SMTP (see `.env`).
- Node OTP server: code may call an external URL in `AuthController::sendOwnWhatsAppOtp`. The Node service itself is started with `npm run whatsapp`.

Tests & Dev workflow notes
- The composer `dev` script runs multiple services concurrently — when changing queue jobs or Mail behavior, run the single services individually to iterate faster (e.g., `php artisan queue:listen`).
- Use `composer run-script test` after changing controllers or models.

Gotchas / non-obvious rules
- `verifyWhatsAppOtp` intentionally returns HTTP 200 with `success: false` for wrong OTPs (legacy Flutter client requirement). Do not change this to a 400 without updating clients.
- When uploading photos, the code explicitly uses the `public` disk and sets `photo_url` to `/storage/{path}`; missing `storage:link` will make images unreachable.
- OTP expiry window: OTPs are cached with a 5-minute TTL across email/telegram/whatsapp flows.

Where to look first (code pointers)
- Authentication & OTP logic: [app/Http/Controllers/AuthController.php](app/Http/Controllers/AuthController.php#L1-L400)
- Provider registration/profile: [app/Http/Controllers/Api/ProviderController.php](app/Http/Controllers/Api/ProviderController.php#L1-L350)
- Routes overview: [routes/api.php](routes/api.php#L1-L40)
- Node WhatsApp server: [server.js](server.js#L1-L200) and start via `npm run whatsapp`.

If something is missing or ambiguous, ask for:
- Which environment should be targeted (local, staging, production)?
- Whether it's allowed to change response shapes (e.g., `verifyWhatsAppOtp`) for new clients.

End of instructions
