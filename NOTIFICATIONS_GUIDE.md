# Laravel Notifications with Queues & Mailpit - Practical Guide

## What is Laravel Notifications?

Laravel Notifications is a system for sending messages to users through different channels:
- **Mail** — Send emails
- **SMS** — Send text messages
- **Slack** — Send Slack messages
- **Database** — Store notifications in a database
- **Custom channels** — Build your own

Each notification can be sent through one or multiple channels.

## How Notifications Work in This Project

### Simple Flow

```
API Request
    ↓
Controller calls notify()
    ↓
Notification is created
    ↓
Notification goes to queue
    ↓
API returns immediately to user
    ↓
Queue worker picks it up
    ↓
Notification sent via mail channel
    ↓
Email appears in Mailpit (dev) or mailbox (production)
```

### Key Difference: Notification vs Job

- **Job** = Raw task (SendWelcomeEmailJob)
- **Notification** = Higher-level message system with multiple channels (WelcomeEmailNotification)

Notifications are better for user-facing messages because they:
- Support multiple channels (email + SMS + Slack)
- Include formatted content
- Are reusable across your app
- Are built for Laravel ecosystem

---

## Files Created for This Project

### 1. `app/Notifications/WelcomeEmailNotification.php`

**Purpose:** The notification class that defines what to send.

**Key parts:**
- `implements ShouldQueue` — Makes it queued
- `use Queueable` — Provides queue functionality
- `via()` method — Specifies channels (mail, SMS, etc.)
- `toMail()` method — Defines email content

**Real-world use:**
- Registration welcome email
- Subscription confirmation
- Invoice email
- Password reset email
- Alert notifications

### 2. `app/Http/Controllers/NotificationDemoController.php`

**Purpose:** Controller that dispatches notifications.

**Key parts:**
- Validates request input
- Creates an `AnonymousNotifiable` (testing without User model)
- Calls `notify()` to dispatch the notification
- Returns standardized API response

**In production:**
```php
// Instead of AnonymousNotifiable, use actual User
$user->notify(new WelcomeEmailNotification($user->name));
```

### 3. `app/Http/Requests/SendNotificationRequest.php`

**Purpose:** Validates request data before sending notification.

**Validates:**
- `name` — recipient name
- `email` — recipient email address

---

## Architecture: How Notifications Integrate with Queues

### Step 1: Dispatch Notification
```php
$user->notify(new WelcomeEmailNotification($user->name));
```

Laravel serializes the notification and adds it to the queue.

### Step 2: Queue Stores It
The notification goes into the `jobs` table in your database:

```
id | queue | payload | attempts | created_at
1  | default | {serialized notification} | 0 | 2026-05-17 10:00:00
```

### Step 3: Worker Picks It Up
```bash
php artisan queue:listen
```

The worker:
- Polls the `jobs` table
- Finds the notification job
- Deserializes it
- Calls `send()` internally

### Step 4: Notification Sends Email
The notification's `toMail()` method is called:
- Email content is created
- Sent to the `mail` channel (SMTP)
- Mailpit captures it in development

### Step 5: Job Removed
Once successful, the job is removed from the queue.

---

## Async Email Flow

### Timeline Without Queues (Sync)
```
10:00:00 - User registers
10:00:02 - Email sent (slow SMTP call blocks)
10:00:02 - User sees success

Problem: User waited 2 seconds just for email sending!
```

### Timeline With Queues (Async)
```
10:00:00 - User registers
10:00:00 - Notification queued
10:00:00 - User sees success (instant!)

Meanwhile in background:
10:00:05 - Queue worker picks up job
10:00:07 - Email sent via Mailpit
```

User gets response immediately. Email happens in background.

---

## Mailpit Workflow with Notifications

### Step 1: Start Mailpit
```powershell
C:\Mailpit\mailpit.exe
```

### Step 2: Configure Laravel (.env)
```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="Laravel Notification SaaS"
```

### Step 3: Start Queue Worker
```bash
php artisan queue:listen
```

### Step 4: Send Notification via API
```
POST /api/demo/send-notification

{
  "name": "John Doe",
  "email": "john@example.com"
}
```

### Step 5: Watch Queue Worker Output
```
Processing: App\Notifications\WelcomeEmailNotification
Processed: App\Notifications\WelcomeEmailNotification
```

### Step 6: Open Mailpit UI
Navigate to `http://127.0.0.1:8025`

You should see the email:
- **From:** no-reply@example.com
- **To:** john@example.com
- **Subject:** (from toMail() method)
- **Body:** Formatted HTML email

---

## How to Dispatch Notifications

### From a Controller
```php
use App\Notifications\WelcomeEmailNotification;

public function register(RegisterRequest $request)
{
    $user = User::create($request->validated());
    
    // Send queued notification
    $user->notify(new WelcomeEmailNotification($user->name));
    
    return ApiResponse::success(...);
}
```

### From a Job
```php
class ProcessPaymentJob implements ShouldQueue
{
    public function handle()
    {
        // ... process payment ...
        
        // Send notification
        $user->notify(new PaymentConfirmationNotification($amount));
    }
}
```

### From an Event
```php
event(new UserRegistered($user));

// Listener:
public function handle(UserRegistered $event)
{
    $event->user->notify(new WelcomeEmailNotification(...));
}
```

---

## How Queue Worker Processes Notifications

### Worker Execution Flow
```bash
$ php artisan queue:listen

[2026-05-17 10:05:00] Processing: App\Notifications\WelcomeEmailNotification
[2026-05-17 10:05:01] Processed: App\Notifications\WelcomeEmailNotification
[2026-05-17 10:05:05] Processing: App\Notifications\PaymentConfirmation
[2026-05-17 10:05:07] Processed: App\Notifications\PaymentConfirmation
```

Worker:
1. Checks `jobs` table continuously
2. Picks the first pending notification
3. Calls the notification's `send()` method
4. Which calls `toMail()` internally
5. Sends via SMTP to Mailpit
6. Removes from queue
7. Waits for next job

---

## How to Verify Email in Mailpit

### Test Steps

1. **API Request**
   ```
   POST http://127.0.0.1:8000/api/demo/send-notification
   
   {
     "name": "Jane Smith",
     "email": "jane@example.com"
   }
   ```

2. **Check Response**
   ```json
   {
     "status": true,
     "message": "Welcome notification has been queued for delivery.",
     "data": {
       "notification_queued": true,
       "recipient": "jane@example.com",
       "message": "Check Mailpit at http://127.0.0.1:8025"
     },
     "errors": null
   }
   ```

3. **Watch Queue Worker**
   - Terminal running `php artisan queue:listen` should show:
   ```
   Processing: App\Notifications\WelcomeEmailNotification
   Processed: App\Notifications\WelcomeEmailNotification
   ```

4. **Open Mailpit UI**
   - Visit `http://127.0.0.1:8025`
   - You should see the email in the list

5. **Click Email to View**
   - Subject: Should match your notification
   - From: `no-reply@example.com`
   - To: `jane@example.com`
   - Body: Formatted HTML from `toMail()` method

---

## Notification Architecture Explained Simply

### The Notification Class
```php
class WelcomeEmailNotification extends Notification implements ShouldQueue
{
    // Properties
    public string $recipientName;

    // Constructor - data passed when notifying
    public function __construct(string $recipientName)
    {
        $this->recipientName = $recipientName;
    }

    // Which channels to use
    public function via(object $notifiable): array
    {
        return ['mail'];  // Could also be ['mail', 'sms', 'slack']
    }

    // How to send via mail channel
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->greeting('Hello ' . $this->recipientName . '!')
            ->line('Welcome to our platform!')
            ->action('Login', url('/'))
            ->salutation('Best regards');
    }
}
```

### The Notifiable Object
The object receiving the notification (usually a User):
```php
// Users can receive notifications
$user->notify(new WelcomeEmailNotification($user->name));

// Or anonymous recipients for testing
$notifiable = (new AnonymousNotifiable())
    ->route('mail', 'test@example.com');
$notifiable->notify(new WelcomeEmailNotification('Test User'));
```

---

## Production Considerations

### When to Send Notifications
- ✅ User registration
- ✅ Payment received
- ✅ Subscription renewed
- ✅ Important alerts
- ✅ Scheduled reports

### Multiple Channels
```php
public function via(object $notifiable): array
{
    return ['mail', 'sms', 'slack'];  // Send via all channels
}

// Different content per channel:
public function toMail(object $notifiable) { ... }
public function toSms(object $notifiable) { ... }
public function toSlack(object $notifiable) { ... }
```

### Retry Configuration
```php
class WelcomeEmailNotification extends Notification implements ShouldQueue
{
    public $tries = 3;  // Retry up to 3 times
    public $backoff = [60, 300, 900];  // Wait 1m, 5m, 15m between retries
}
```

---

## Common Beginner Mistakes

### Mistake 1: Forgetting `implements ShouldQueue`
```php
// ❌ Won't queue
class MyNotification extends Notification
{
    public function via($notifiable) { return ['mail']; }
}

// ✅ Queues properly
class MyNotification extends Notification implements ShouldQueue
{
    public function via($notifiable) { return ['mail']; }
}
```

### Mistake 2: Not Starting Queue Worker
```
Notifications queued but not sent because no worker running!

Fix: php artisan queue:listen
```

### Mistake 3: Forgetting `use Queueable`
```php
// ❌ Missing trait
class MyNotification extends Notification implements ShouldQueue
{
    // Queueable trait not imported
}

// ✅ Correct
class MyNotification extends Notification implements ShouldQueue
{
    use Queueable;
}
```

### Mistake 4: Mailpit not running
```
Emails queue but disappear because Mailpit isn't capturing them.

Fix: Start C:\Mailpit\mailpit.exe
```

### Mistake 5: Wrong mail configuration
```env
❌ MAIL_MAILER=log  (goes to log file, not Mailpit)
✅ MAIL_MAILER=smtp
✅ MAIL_HOST=127.0.0.1
✅ MAIL_PORT=1025
```

---

## Summary

Laravel Notifications provide a clean, reusable way to send messages:
- Queue them for background processing
- Use Mailpit to test emails locally
- Support multiple channels (email, SMS, Slack, etc.)
- Keep controllers thin and business logic in notification classes

For your SaaS:
1. Create notification classes for each message type
2. Dispatch from controllers or jobs
3. Queue worker sends them asynchronously
4. Use Mailpit to verify locally
5. Switch to real mail service in production

This keeps your API fast while ensuring users get important notifications reliably.
