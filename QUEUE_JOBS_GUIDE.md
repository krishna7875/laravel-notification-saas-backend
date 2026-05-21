# Laravel Queue & Job System Guide

## Quick Summary

**Job** = A task to be done (send email, process payment, etc.)
**Queue** = A line where jobs wait to be processed
**Worker** = A process that picks jobs from the queue and executes them

Think of it like a restaurant kitchen:
- **Job** = Customer order (send email notification)
- **Queue** = Plate line (jobs waiting to be processed)
- **Worker** = Chef (processes jobs one by one)

---

## Problem: Why Queues Matter

### Without Queues (Synchronous)
```
User clicks "Register"
    ↓
App creates user
    ↓
App sends welcome email (blocks user for 5 seconds)
    ↓
App sends SMS notification (blocks user for 3 seconds)
    ↓
App sends push notification (blocks user for 2 seconds)
    ↓
User sees success (after 10 seconds!)
```

**Problem:** User waits 10 seconds even though registration took 0.5 seconds.
Email/SMS/push services are slow—why should user wait?

### With Queues (Asynchronous)
```
User clicks "Register"
    ↓
App creates user
    ↓
App adds tasks to queue:
  - SendWelcomeEmail job
  - SendSmsNotification job
  - SendPushNotification job
    ↓
App returns success to user immediately (0.5 seconds)
    ↓
Background worker picks jobs and processes them:
  - Sends email (in background)
  - Sends SMS (in background)
  - Sends push (in background)
```

**Benefit:** User sees success immediately. Heavy tasks happen in background.

---

## Key Concepts

### 1. Sync vs Async Processing

#### Sync (Synchronous)
```
Task 1 → Task 2 → Task 3 → User Response
(waits for all tasks)
```
- Blocking: Each task must finish before next starts
- Fast for simple tasks
- Slow for heavy operations
- User waits for everything

#### Async (Asynchronous)
```
User Request
    ↓
    ├→ Task 1 (background)
    ├→ Task 2 (background)
    ├→ Task 3 (background)
    ↓
Immediate Response to User
```
- Non-blocking: Tasks happen in background
- User gets response immediately
- Heavy operations don't slow down UI
- Tasks process when worker is ready

### 2. What is a Job?

A **Job** is a class that represents a task to be done.

**Real example: SendWelcomeEmailJob**
```php
namespace App\Jobs;

use App\Models\User;

class SendWelcomeEmailJob
{
    public function __construct(public User $user)
    {
    }

    public function handle()
    {
        // Send email to user
        Mail::to($this->user->email)->send(new WelcomeEmail($this->user));
    }
}
```

When you need to send email, instead of calling it directly:
```php
// ❌ Old way (blocks user)
Mail::to($user->email)->send(new WelcomeEmail($user));

// ✅ New way (queues job, returns immediately)
SendWelcomeEmailJob::dispatch($user);
```

### 3. What is a Queue?

A **Queue** is storage for pending jobs.

In this project:
- Queue driver: **database** (uses MySQL `jobs` table)
- Jobs wait in `jobs` table until processed
- Failed jobs go to `failed_jobs` table

**Queue Flow:**
```
User Action
    ↓
Job pushed to queue (stored in `jobs` table)
    ↓
Worker reads from queue
    ↓
Job executed
    ↓
Job removed from queue
    ↓
Success ✓ OR Failure ✗
```

### 4. Queue Workers

A **Worker** is a background process that:
- Polls the queue continuously
- Picks one job at a time
- Executes the job
- Removes completed job from queue
- Repeats

**Start a queue worker:**
```bash
php artisan queue:listen
```

This command:
- Starts a background process
- Checks `jobs` table every second
- Picks the first pending job
- Runs it
- Waits for next job
- Runs forever until stopped

---

## Current Project Setup

### Queue Driver: Database

Your project uses the **database** queue driver. This means:

**Jobs Table (`jobs`)**
```
id | queue | payload | attempts | reserved_at | available_at | created_at
```

- Each row = one job waiting to be processed
- `payload` = serialized job data
- `attempts` = how many times job has been tried
- When worker picks a job, it marks it `reserved`
- When job fails, it goes to `failed_jobs` table

**Why database driver?**
- ✅ No external dependencies (Redis not needed initially)
- ✅ Works with existing MySQL database
- ✅ Easy to debug (see jobs in database)
- ✅ Good for learning
- ⚠️ Slower than Redis for high volume
- Plan: Switch to **Redis** later for production performance

---

## Queue Workflow Step-by-Step

### Step 1: Create a Job Class

```bash
php artisan make:job SendWelcomeEmail
```

This creates `app/Jobs/SendWelcomeEmail.php`:

```php
<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWelcomeEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user)
    {
    }

    public function handle()
    {
        // Send email
        Mail::to($this->user->email)->send(new WelcomeEmail($this->user));
    }
}
```

### Step 2: Dispatch Job from Controller

```php
namespace App\Http\Controllers;

use App\Jobs\SendWelcomeEmail;
use App\Models\User;

class AuthController
{
    public function register(RegisterRequest $request)
    {
        // Create user
        $user = User::create($request->validated());

        // Dispatch job (queued immediately)
        SendWelcomeEmail::dispatch($user);

        // Return response to user
        return ApiResponse::success([...], 'User registered');
    }
}
```

### Step 3: Start Queue Worker

```bash
php artisan queue:listen
```

Worker output:
```
Processing: App\Jobs\SendWelcomeEmail
Processed: App\Jobs\SendWelcomeEmail
```

### Step 4: Job Executed

When worker picks the job:
1. Deserializes job data
2. Calls `handle()` method
3. Removes from `jobs` table
4. Job complete ✓

---

## Retries & Failed Jobs

### What Happens When Job Fails?

If `handle()` throws exception:

```
Job 1: SendWelcomeEmail
    ↓
Exception thrown (email service down)
    ↓
Retry 1 (waits 60 seconds)
    ↓
Exception thrown again
    ↓
Retry 2 (waits 120 seconds)
    ↓
Exception thrown again
    ↓
Max retries reached
    ↓
Job moved to `failed_jobs` table
```

### Configure Retries

In Job class:
```php
class SendWelcomeEmail implements ShouldQueue
{
    // Try max 3 times
    public $tries = 3;

    // Wait before retry (seconds)
    public $backoff = [60, 120, 300];

    public function handle()
    {
        // Code here
    }
}
```

### View Failed Jobs

```bash
# List all failed jobs
php artisan queue:failed

# Retry a failed job
php artisan queue:retry <id>

# Forget a failed job (delete)
php artisan queue:forget <id>
```

---

## How Queues Will Be Used in This SaaS

### 1. Email Sending

**Current (Synchronous):**
```php
// Blocks user while sending email
Mail::to($user->email)->send(new WelcomeEmail($user));
```

**With Queues (Async):**
```php
// Queues job, returns immediately
SendWelcomeEmail::dispatch($user);

// Worker sends email in background
```

**Real Use Cases:**
- Welcome email after registration
- Password reset email
- Subscription confirmation email
- Invoice email
- All heavy operations → queue

### 2. SMS Sending

```php
class SendSmsNotification implements ShouldQueue
{
    public function handle()
    {
        // Call Twilio/MSG91 API
        Twilio::sendMessage($this->user->phone, 'Your code: 1234');
    }
}
```

When subscription changes:
```php
SubscriptionUpdated::dispatch($user);
// SMS sent in background
```

### 3. Push Notifications

```php
class SendPushNotification implements ShouldQueue
{
    public function handle()
    {
        // Send via Firebase FCM
        Firebase::send($this->device->fcm_token, 'New message');
    }
}
```

### 4. Payment Webhooks

**Critical:** Webhook processing must be fast. Using queues:

```php
class HandlePaymentWebhook implements ShouldQueue
{
    public $tries = 5; // Important: retry failed webhooks
    public $backoff = [60, 300, 900];

    public function handle()
    {
        // Verify webhook signature
        // Update subscription status
        // Send confirmation email
        // Log transaction
    }
}
```

When Stripe sends payment webhook:
```php
Route::post('/webhooks/stripe', function (Request $request) {
    // Quickly queue job
    HandlePaymentWebhook::dispatch($request->all());

    // Return 200 OK immediately
    return response()->json(['success' => true]);
});
```

**Why?** Stripe expects response within 5 seconds. If you process everything synchronously, you'll timeout. Queues keep response fast.

### 5. Heavy Data Processing

```php
class ProcessBulkEmails implements ShouldQueue
{
    public function handle()
    {
        // Send 10,000 emails
        // If synchronous: takes 10 minutes → timeout
        // If queued: happens in background
    }
}
```

---

## Production Workflow

### Development (Now)
```
php artisan queue:listen
    ↓
Worker processes jobs from database
    ↓
Good for testing and learning
```

### Production (Later)
```
Multiple queue workers:
  - Worker 1: Process emails
  - Worker 2: Process SMS
  - Worker 3: Process payments
  - Worker 4: Process webhooks

With Redis queue (fast in-memory storage)
Monitored by Supervisor (auto-restart if crashed)
```

---

## Common Beginner Mistakes

### Mistake 1: Not Implementing ShouldQueue
```php
// ❌ Won't queue
class SendEmail
{
    public function handle() { }
}

// ✅ Queues properly
class SendEmail implements ShouldQueue
{
    public function handle() { }
}
```

### Mistake 2: Forgetting to Start Worker
```
// Jobs queued but not processed
// Because no worker running to pick them up

# Fix:
php artisan queue:listen
```

### Mistake 3: Job Lost Due to Serialization
```php
// ❌ Won't work (Request not serializable)
class MyJob implements ShouldQueue
{
    public function __construct(public Request $request) { }
}

// ✅ Pass only IDs
class MyJob implements ShouldQueue
{
    public function __construct(public int $userId) { }
    
    public function handle()
    {
        $user = User::find($this->userId); // Fetch fresh
    }
}
```

### Mistake 4: Not Handling Job Failures
```php
// ❌ Job fails, goes to failed_jobs table, forgotten
class BadJob implements ShouldQueue
{
    public function handle()
    {
        External::api()->call(); // Might fail
    }
}

// ✅ Configure retries
class GoodJob implements ShouldQueue
{
    public $tries = 3;
    public $backoff = [60, 300];
    
    public function handle()
    {
        try {
            External::api()->call();
        } catch (\Exception $e) {
            $this->fail($e);
        }
    }
}
```

### Mistake 5: Queuing Too Much
```php
// ❌ Don't queue simple operations
ImageResize::dispatch($image); // Takes 1ms, not worth queuing

// ✅ Queue heavy operations only
SendEmail::dispatch($user); // Takes 2 seconds, worth queuing
PaymentProcess::dispatch($order); // External API call, must queue
```

---

## Quick Reference

### Create a Job
```bash
php artisan make:job SendEmail
```

### Dispatch from Controller
```php
use App\Jobs\SendEmail;

SendEmail::dispatch($user);
// Or with delay
SendEmail::dispatch($user)->delay(now()->addMinutes(5));
```

### Start Worker
```bash
php artisan queue:listen
```

### View Queue Table
```bash
# In terminal
mysql> SELECT * FROM jobs;
```

### Failed Jobs
```bash
php artisan queue:failed          # List failed
php artisan queue:retry 1         # Retry by ID
php artisan queue:forget 1        # Delete by ID
```

### Configuration
File: `config/queue.php`
```php
'default' => env('QUEUE_CONNECTION', 'database'),
```

In `.env`:
```
QUEUE_CONNECTION=database
DB_QUEUE_TABLE=jobs
```

---

## Future: From Database to Redis

As project scales, upgrade to Redis:

### Why Redis?
- ✅ Faster (in-memory)
- ✅ Better for high volume
- ✅ Industry standard for queues
- ✅ Supports more features

### How to Switch
1. Install Redis server
2. Change `.env`: `QUEUE_CONNECTION=redis`
3. Restart workers
4. Done! No code changes needed

---

## Summary

- **Jobs** = Tasks (send email, SMS, process payment)
- **Queues** = Storage for pending jobs (database table)
- **Workers** = Background processes executing jobs
- **Sync** = Blocks user, slow, simple
- **Async** = Returns immediately, user doesn't wait, job happens in background
- **Retries** = Failed jobs automatically retry
- **Failed Jobs** = Moved to `failed_jobs` table after max retries
- **This Project** = Uses database queue for development, switch to Redis later
- **Why?** Keep API fast, move heavy work to background

Queue your heavy operations. Keep your API fast. That's the key to responsive SaaS.
