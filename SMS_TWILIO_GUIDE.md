# Queued SMS Notifications with Twilio - Practical Guide

## What is Twilio?

Twilio is a cloud platform that lets you send SMS, make calls, and send messages programmatically via their API.

For this project, we use Twilio to:
- Send SMS messages from Laravel
- Reach users via phone, not just email
- Integrate SMS with queues for background processing
- Handle SMS retries automatically

---

## SMS Workflow in This SaaS

### High-Level Overview

```
User Action (register, payment, alert)
    ↓
Controller receives request
    ↓
Validates input (phone, message)
    ↓
Dispatches SendSmsJob to queue
    ↓
Returns success to user immediately (200 OK)
    ↓
[Background] Queue worker picks up job
    ↓
[Background] Job calls SmsService::send()
    ↓
[Background] SmsService calls Twilio API
    ↓
[Background] Twilio sends SMS to phone
    ↓
SMS received by user
```

User gets API response instantly. SMS happens in background.

---

## Why Use a Service Class?

The `SmsService` class centralizes all SMS logic:

**Benefits:**
- **Single source of truth** — All SMS logic in one place
- **Easy to maintain** — Changes needed? Modify one file
- **Easy to test** — Mock SmsService in tests
- **Easy to swap providers** — Switch from Twilio to AWS SNS? Change only SmsService
- **Reusable** — Multiple jobs and controllers can use it
- **Keeps controllers thin** — Controllers just dispatch, don't handle SMS logic

**Architecture:**
```
SmsDemoController (thin)
    ↓ dispatches
SendSmsJob (async wrapper)
    ↓ calls
SmsService (business logic)
    ↓ calls
Twilio API (external service)
```

---

## Why Queue SMS Sending?

SMS API calls take time:
- Connect to Twilio
- Send request
- Wait for response
- Verify delivery

**Without queue (blocking):**
```
User registers
    ↓
App sends SMS (2 seconds wait)
    ↓
User sees success

Problem: User waits 2 seconds for SMS!
```

**With queue (async):**
```
User registers
    ↓
Job queued (instant)
    ↓
User sees success (0.1 seconds)
    ↓
[Meanwhile] Worker sends SMS in background
```

User gets response immediately.

---

## Files Created

### 1. `app/Services/SmsService.php`

**Purpose:** All Twilio integration logic lives here.

**Methods:**
- `send($phone, $message)` — Generic SMS sending
- `sendWelcomeSms($phone, $name)` — Welcome message
- `sendOtpSms($phone, $otp)` — One-time password
- `sendVerificationSms($phone)` — Account verification

**Key feature:** Logs SMS in development. In production, sends via Twilio API.

### 2. `app/Jobs/SendSmsJob.php`

**Purpose:** Queued job that sends SMS asynchronously.

**Features:**
- `implements ShouldQueue` — Makes it async
- `$tries = 3` — Retry 3 times if it fails
- `$backoff = [60, 300, 900]` — Wait 1m, 5m, 15m between retries
- Dependency injection: `handle(SmsService $smsService)`
- `failed()` method logs permanent failures

**Workflow:**
1. Controller dispatches this job
2. Job stored in queue
3. Worker picks it up
4. Calls `handle()` method
5. Service sends SMS
6. Job removed from queue

### 3. `app/Http/Requests/SendSmsRequest.php`

**Purpose:** Validates SMS request data.

**Validates:**
- `phone_number` — Must be E.164 format (+1234567890)
- `message` — Max 160 characters (SMS limit)

**Why E.164?**
International standard phone number format:
- `+1` = country code (US)
- `2025551234` = phone number
- Result: `+12025551234`

### 4. `app/Http/Controllers/SmsDemoController.php`

**Purpose:** API endpoints for sending SMS.

**Endpoints:**
- `POST /api/demo/send-sms` — Send custom SMS
- `POST /api/demo/send-welcome-sms` — Send welcome SMS

**Pattern:** Thin controller that validates and dispatches.

---

## Twilio Setup

### Step 1: Create Twilio Account

1. Go to https://www.twilio.com
2. Sign up for free account
3. Get free credits ($15)
4. Complete phone verification

### Step 2: Get Twilio Credentials

In Twilio Console:
1. Find your `Account SID` (looks like: `ACxxxxxxxxxxxxxxxxxxxxxx`)
2. Find your `Auth Token` (looks like: `xxxxxxxxxxxxxxxxxxxxxx`)
3. Get a `Phone Number` (Twilio provides one for testing)

Example from Twilio console:
```
Account SID: AC1234567890abcdefghij1234567890
Auth Token:  1234567890abcdefghij1234567890ab
Phone:       +12025551234
```

### Step 3: Add to `.env` File

Open `backend/.env` and add:

```env
TWILIO_ACCOUNT_SID=AC1234567890abcdefghij1234567890
TWILIO_AUTH_TOKEN=1234567890abcdefghij1234567890ab
TWILIO_PHONE_NUMBER=+12025551234
```

### Step 4: Configure `config/services.php`

Add Twilio config if not already present:

```php
'twilio' => [
    'account_sid' => env('TWILIO_ACCOUNT_SID'),
    'auth_token' => env('TWILIO_AUTH_TOKEN'),
    'phone_number' => env('TWILIO_PHONE_NUMBER'),
],
```

### Step 5: Install Twilio PHP SDK

```bash
composer require twilio/sdk
```

---

## Environment Variables Explained

### `TWILIO_ACCOUNT_SID`
- Your Twilio account identifier
- Find in Twilio Console → Account Settings
- Looks like: `ACxxxxx...`

### `TWILIO_AUTH_TOKEN`
- Your Twilio API authentication token
- Find in Twilio Console → Account Settings
- Keep this secret! Never commit to git.

### `TWILIO_PHONE_NUMBER`
- The SMS sender phone number
- Assigned by Twilio when you create an account
- Format: `+1234567890` (E.164 format)

---

## How to Test Safely

### Step 1: Start Queue Worker

```bash
cd backend
php artisan queue:listen
```

Watch for output:
```
Processing: App\Jobs\SendSmsJob
Processed: App\Jobs\SendSmsJob
```

### Step 2: Check Development Mode

In `SmsService`, SMS sending is commented out. It only logs:

```php
// For development/testing, just log the SMS
Log::info('SMS sent successfully', [
    'phone' => $phoneNumber,
    'message' => $message,
]);
```

This prevents test SMS from going to real numbers.

### Step 3: Send Test Request

**Postman:**
```
POST http://127.0.0.1:8000/api/demo/send-sms

{
  "phone_number": "+12025551234",
  "message": "Hello! This is a test SMS."
}
```

### Step 4: Verify in Logs

Check `storage/logs/laravel.log`:

```
[2026-05-17 10:15:30] local.INFO: SMS sent successfully {"phone":"+12025551234","message":"Hello! This is a test SMS."}
```

### Step 5: Enable Real Twilio (Production)

When ready for production, uncomment in `SmsService`:

```php
$twilio = new \Twilio\Rest\Client(
    config('services.twilio.account_sid'),
    config('services.twilio.auth_token')
);

$twilio->messages->create(
    $phoneNumber,
    [
        'from' => config('services.twilio.phone_number'),
        'body' => $message,
    ]
);
```

Then SMS will actually be sent to real phone numbers.

---

## Async SMS Processing Explained

### Request-Response Cycle
```
POST /api/demo/send-sms
    ↓
Validate phone + message
    ↓
Dispatch SendSmsJob
    ↓
Return 202 Accepted
    ↓
Connection closed
    ↓
User sees success (0.1 seconds)
```

### Background Processing (After response)
```
Queue worker running...
    ↓
Worker sees SendSmsJob in queue
    ↓
Worker calls handle() method
    ↓
SmsService sends SMS via Twilio
    ↓
Job removed from queue
    ↓
SMS delivered to user's phone
```

**Key:** User's API request finishes before SMS is sent. That's the beauty of async!

---

## Real Use Cases in Your SaaS

### 1. Welcome SMS After Registration
```php
// In AuthController
public function register(RegisterRequest $request)
{
    $user = User::create($request->validated());
    
    SendSmsJob::dispatch($user->phone, "Welcome to our SaaS!");
    
    return ApiResponse::success([...], 'Registered successfully');
}
```

### 2. OTP for 2FA
```php
// In TwoFactorController
public function sendOtp(Request $request)
{
    $otp = mt_rand(100000, 999999);
    
    SendSmsJob::dispatch($request->user()->phone, "Your OTP: {$otp}");
    
    return ApiResponse::success([...], 'OTP sent');
}
```

### 3. Payment Notification
```php
// In PaymentController
public function processPayment(PaymentRequest $request)
{
    $payment = $this->paymentService->process($request->all());
    
    SendSmsJob::dispatch(
        $payment->user->phone,
        "Payment of \${$payment->amount} confirmed!"
    );
    
    return ApiResponse::success($payment);
}
```

### 4. Subscription Renewal Alert
```php
// In SubscriptionController
public function renewSubscription($subscriptionId)
{
    $subscription = Subscription::find($subscriptionId);
    
    SendSmsJob::dispatch(
        $subscription->user->phone,
        "Your subscription has been renewed. Thank you!"
    );
    
    return ApiResponse::success([...]);
}
```

---

## Job Retry Configuration

### Default Retries
```php
class SendSmsJob implements ShouldQueue
{
    public int $tries = 3;                    // Try 3 times total
    public array $backoff = [60, 300, 900];   // Wait times
}
```

**Timeline if SMS fails:**
```
Attempt 1: 10:00:00 - Fails (Twilio API down)
    ↓
Wait 60 seconds (backoff)
    ↓
Attempt 2: 10:01:00 - Fails (network error)
    ↓
Wait 300 seconds (backoff)
    ↓
Attempt 3: 10:06:00 - Fails (permanent failure)
    ↓
Job moves to failed_jobs table
```

### Custom Retries for Critical SMS
```php
class CriticalOtpSmsJob implements ShouldQueue
{
    public int $tries = 5;  // Try 5 times
    public array $backoff = [30, 60, 120, 300, 600];  // More aggressive
}
```

---

## Common Beginner Mistakes

### Mistake 1: Phone number format wrong
```
❌ "2025551234"        (missing + and country code)
❌ "+1 (202) 555-1234" (has spaces and parentheses)
✅ "+12025551234"      (E.164 format)
```

### Mistake 2: Message too long
```
❌ 200 characters (exceeds SMS limit)
✅ 160 characters (standard SMS size)
```

### Mistake 3: Forgetting to start queue worker
```
SMS queued but not sent because no worker running!

Fix: php artisan queue:listen
```

### Mistake 4: Twilio credentials in git
```
❌ TWILIO_AUTH_TOKEN hardcoded in code
❌ Credentials in .env file committed to git

✅ Use .env.example with placeholder values
✅ Add .env to .gitignore
✅ Set credentials in production via environment
```

### Mistake 5: Not handling SMS failures
```php
❌ Fire and forget, no retry config
class BadSmsJob implements ShouldQueue
{
    // No retry configuration
}

✅ Configure retries
class GoodSmsJob implements ShouldQueue
{
    public int $tries = 3;
    public array $backoff = [60, 300, 900];
    
    public function failed(\Throwable $e): void
    {
        Log::error('SMS failed permanently', ['error' => $e->getMessage()]);
    }
}
```

---

## Twilio Free Trial Limits

During free trial:
- Can send SMS to verified phone numbers only
- Cannot send to arbitrary numbers
- Limited to business hours (sometimes)
- After free credits end, upgrade to paid plan

To add verified numbers:
1. Twilio Console → Phone Numbers → Verified Numbers
2. Add your personal phone number
3. Verify via SMS code
4. Now you can send SMS to that number

---

## Testing API Endpoints

### Test 1: Send Custom SMS
```
POST /api/demo/send-sms

{
  "phone_number": "+12025551234",
  "message": "Test message"
}
```

Response (202 Accepted):
```json
{
  "status": true,
  "message": "SMS queued successfully for delivery.",
  "data": {
    "queued": true,
    "phone_number": "+12025551234",
    "message": "SMS has been queued. Check logs for delivery status."
  },
  "errors": null
}
```

### Test 2: Send Welcome SMS
```
POST /api/demo/send-welcome-sms

{
  "phone_number": "+12025551234",
  "message": "Your phone number (ignored, uses template)"
}
```

### Test 3: Invalid Phone Number
```
POST /api/demo/send-sms

{
  "phone_number": "202-555-1234",
  "message": "Test"
}
```

Response (422):
```json
{
  "status": false,
  "message": "The given data was invalid.",
  "data": null,
  "errors": {
    "phone_number": ["Phone number must be in E.164 format (e.g., +1234567890)"]
  }
}
```

---

## Summary

**SMS Architecture:**
- **Service** → Business logic (SmsService)
- **Job** → Async execution (SendSmsJob)
- **Controller** → Request handling (SmsDemoController)
- **Queue** → Background processing

**Twilio Integration:**
- Get credentials from Twilio Console
- Store in `.env` safely
- Service handles API calls
- Job queues SMS for background delivery

**Async Processing:**
- User gets API response instantly
- SMS sent in background via worker
- Job retries automatically on failure
- Failed jobs stored for manual review

This pattern scales to handle thousands of SMS without slowing down your API.
