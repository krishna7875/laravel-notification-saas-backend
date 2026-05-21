# Mailpit Setup Guide for Laravel 12 on Windows with Laragon

## What is Mailpit?

Mailpit is a local email testing tool. It acts like a fake SMTP server that accepts emails sent by your application and stores them in a simple web interface.

### Why use Mailpit in local development?
- It prevents test emails from being sent to real users.
- It lets you inspect email content quickly.
- It is faster and safer than using a real mail service in development.
- It works well with Laravel’s mail system.

## How Mailpit works with Laravel

Laravel sends email through an SMTP server. In local development, Mailpit becomes that SMTP server.
- Laravel uses values from `.env` such as `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, and `MAIL_FROM_*`.
- When Laravel sends email, Mailpit captures the message instead of delivering it to the internet.
- You can open the Mailpit UI in a browser and view received emails.

## Mailpit installation steps on Windows with Laragon

### Step 1: Download Mailpit
1. Open your browser and go to: https://github.com/axllent/mailpit
2. Find the latest Windows release.
3. Download the `mailpit-windows-amd64.exe` file.

### Step 2: Place Mailpit in a folder
1. Create a folder such as `C:\Mailpit`.
2. Move the downloaded `mailpit-windows-amd64.exe` file into that folder.
3. Rename it to `mailpit.exe` for convenience.

### Step 3: Start Mailpit
1. Open `cmd.exe` or PowerShell.
2. Change directory to the Mailpit folder:
   ```powershell
   cd C:\Mailpit
   ```
3. Start Mailpit by running:
   ```powershell
   .\mailpit.exe
   ```
4. If you want Mailpit to listen on a custom port, use:
   ```powershell
   .\mailpit.exe --smtp-bind-addr 127.0.0.1:1025 --http-bind-addr 127.0.0.1:8025
   ```

## How to verify Mailpit is running
1. Open your browser.
2. Go to: `http://127.0.0.1:8025`
3. If Mailpit is running, you should see the Mailpit UI.
4. You can also check the terminal where Mailpit is running for startup messages.

## Laravel `.env` mail configuration

Open `backend/.env` and set the mail values like this:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="Laravel Notification SaaS"
```

### What each setting means
- `MAIL_MAILER`
  - This selects the mail driver.
  - Use `smtp` when sending mail through Mailpit.
- `MAIL_HOST`
  - The SMTP server host.
  - For Mailpit on your machine, use `127.0.0.1`.
- `MAIL_PORT`
  - The SMTP port Mailpit listens on.
  - The default Mailpit SMTP port is `1025`.
- `MAIL_FROM_ADDRESS`
  - The sender email address shown in outgoing messages.
  - Use a development-friendly address like `no-reply@example.com`.
- `MAIL_FROM_NAME`
  - The sender name shown in outgoing messages.
  - Example: `Laravel Notification SaaS`.

## How SMTP works in this setup

1. Laravel creates the email content.
2. Laravel connects to Mailpit at `127.0.0.1:1025`.
3. Mailpit receives the email via SMTP.
4. Mailpit stores the email and shows it in the browser UI.

No real email is sent to the outside world.

## How to test email sending

### Step 1: Make sure Mailpit is running
- Open `http://127.0.0.1:8025` in your browser.
- Confirm the UI is available.

### Step 2: Send a test email from Laravel
- If you have an existing email route or command, use it.
- Otherwise, create a simple route in `routes/api.php` or a controller.

Example route for quick testing (in `routes/api.php`):
```php
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/mailpit-test', function () {
    Mail::raw('Hello from Mailpit test', function ($message) {
        $message->to('test@example.com')
                ->subject('Mailpit Test Email');
    });

    return response()->json(['status' => true, 'message' => 'Mail sent to Mailpit.']);
});
```

### Step 3: Visit the test route
- Open `http://127.0.0.1:8000/api/mailpit-test` if your Laravel server is running on port 8000.
- The API should return success immediately.

## How to open Mailpit UI
1. Open browser.
2. Navigate to `http://127.0.0.1:8025`.
3. You should see the message list.
4. Click a message to view subject, from, to, and body.

## How to verify received emails
- In Mailpit UI, check the list of captured emails.
- Confirm the subject line and recipient match your test.
- Open the message to verify the email body.
- If you sent HTML, the preview should show the rendered content.

## Common beginner mistakes and fixes

### Mistake 1: Mailpit is not running
- Fix: Start Mailpit with `mailpit.exe` and verify `http://127.0.0.1:8025`.

### Mistake 2: Wrong port in `.env`
- Fix: Match `MAIL_PORT` with Mailpit’s SMTP port. Default is `1025`.

### Mistake 3: `MAIL_MAILER` is not `smtp`
- Fix: Set `MAIL_MAILER=smtp` in `.env`.

### Mistake 4: Old `.env` values cached
- Fix: Run:
  ```bash
  php artisan config:clear
  php artisan cache:clear
  ```

### Mistake 5: Using `localhost` instead of `127.0.0.1`
- Sometimes Windows resolves `localhost` differently. Use `127.0.0.1` for the mail host.

### Mistake 6: No `MAIL_FROM_ADDRESS` configured
- Fix: Add `MAIL_FROM_ADDRESS=no-reply@example.com` and `MAIL_FROM_NAME="Laravel Notification SaaS"`.

## Summary

For local Laravel development with Laragon, Mailpit is a lightweight SMTP catcher that helps you test emails safely. Use `.env` settings to point Laravel to Mailpit, start the Mailpit app, send a test email, and verify it in the browser UI.

This setup keeps your backend-focused SaaS project safe and practical during development.
