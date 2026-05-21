# Twilio Test API & .env Setup Guide

This guide shows how to safely configure and test Twilio SMS for the Laravel Notification SaaS project.
Keep it beginner-friendly and focused on backend usage.

---

## What this file covers
- Required Twilio environment variables
- How to put them in `backend/.env`
- How to keep secrets out of Git
- How to test the Twilio API safely (Postman / cURL)
- How to test via the project endpoints (`/api/demo/send-sms`)
- How to verify delivery and debug

---

## Required environment variables
Add the following to `backend/.env` (replace example values):

```env
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_PHONE_NUMBER=+12025551234
TWILIO_ENABLED=false
```

- `TWILIO_ACCOUNT_SID` — Your Twilio Account SID (from Twilio Console).
- `TWILIO_AUTH_TOKEN` — Your Twilio Auth Token (secret; do NOT commit).
- `TWILIO_PHONE_NUMBER` — Twilio phone number you will send SMS from (E.164 format).
- `TWILIO_ENABLED` — Optional boolean flag (recommended) to enable/disable real sending.
  - Keep this `false` in development so you don't accidentally send SMS.

Note: `SmsService.php` in this project currently calls Twilio directly. Use `TWILIO_ENABLED=false` to avoid sending real SMS in development, or change the service to check this flag.

---

## Keep secrets out of Git
1. Ensure `backend/.env` is listed in `.gitignore` (it should be by default).
2. Create `backend/.env.example` with placeholder values (commit this file).
3. Never paste your `TWILIO_AUTH_TOKEN` into public repos or chat.

Example `.env.example` snippet:
```env
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_PHONE_NUMBER=+1XXXXXXXXXX
TWILIO_ENABLED=false
```

---

## Quick Twilio Console checks
- Login to https://www.twilio.com/console
- Note your **Account SID** and **Auth Token**
- Buy or provision a Twilio phone number (in trial you get one)
- If on trial, you can only send SMS to verified numbers (add them under Verified Caller IDs)

---

## How to test Twilio REST API directly (optional)

This helps verify credentials before using your app.

### Using cURL (replace placeholders):
```bash
curl -X POST "https://api.twilio.com/2010-04-01/Accounts/$TWILIO_ACCOUNT_SID/Messages.json" \
--data-urlencode "To=+1RECIPIENTNUMBER" \
--data-urlencode "From=$TWILIO_PHONE_NUMBER" \
--data-urlencode "Body=Hello from Twilio test" \
-u "$TWILIO_ACCOUNT_SID:$TWILIO_AUTH_TOKEN"
```

Successful response: HTTP 201 with JSON about the message.
If you get HTTP 401, check your SID/auth token.
If you get 400 or 403, check the `To` number verification (trial accounts) or number formatting.

---

## How to test through the Laravel API endpoint (recommended)

This project exposes demo endpoints for SMS testing:
- `POST /api/demo/send-sms` — custom SMS
- `POST /api/demo/send-welcome-sms` — templated welcome SMS

### Example Postman / cURL request (send-sms)

POST http://127.0.0.1:8000/api/demo/send-sms

Body (JSON):
```json
{
  "phone_number": "+12025551234",
  "message": "Hello! This is a test SMS from the Laravel Notification SaaS."
}
```

The endpoint will:
- Validate input (E.164 phone format, message length)
- Queue `SendSmsJob` (returns 202 Accepted)
- The job will call `SmsService::send()` when a worker processes it

### Start the queue worker
Run in backend folder:
```bash
php artisan queue:listen
```
You should see worker output when the job is processed.

### Verify results
- In development with `TWILIO_ENABLED=false`: check `storage/logs/laravel.log` for a `SMS sent successfully` entry.
- With `TWILIO_ENABLED=true`: check Twilio Console → Messaging → Logs for the message; the recipient phone should receive the SMS (trial restrictions apply).

---

## How `TWILIO_ENABLED` affects the code (recommended pattern)

If you prefer to avoid changing `SmsService.php` now, use the `TWILIO_ENABLED` flag and adjust the service like this (conceptual):

```php
if (! config('services.twilio.enabled')) {
    // Log only
    Log::info('SMS simulated', compact('phoneNumber', 'message'));
    return true;
}

// Otherwise send with Twilio SDK
```

This prevents accidental real SMS sending during development.

---

## Troubleshooting & common issues

- 401 Unauthorized from Twilio API: check `TWILIO_ACCOUNT_SID` and `TWILIO_AUTH_TOKEN`.
- 400 Bad Request: invalid phone number format. Use E.164 (`+1234567890`).
- Trial account restrictions: verify recipient numbers in Twilio Console.
- No jobs processed: start queue worker (`php artisan queue:listen`).
- No logs: ensure `LOG_CHANNEL` in `.env` writes to file (default `stack` should).

---

## Safe testing checklist (before enabling real SMS sends)
1. Set `TWILIO_ENABLED=false` in `.env` while developing.
2. Use `TWILIO_ENABLED=true` only in staging/production with real credentials.
3. Use verified numbers for Twilio trial accounts.
4. Monitor `storage/logs/laravel.log` for job output.
5. Use Twilio console logs to inspect outgoing messages.

---

## Quick reference: Example `.env` block
```env
# Twilio credentials (do NOT commit to git)
TWILIO_ACCOUNT_SID=AC1234567890abcdef1234567890abcd
TWILIO_AUTH_TOKEN=abcdefghijklmnopqrstuvwxyz123456
TWILIO_PHONE_NUMBER=+12025551234
TWILIO_ENABLED=false
```

---

If you want, I can:
- Add the `TWILIO_ENABLED` guard into `SmsService.php` for safe-by-default behavior, or
- Add a small test route that simulates Twilio responses without sending real SMS.

Which would you prefer?"