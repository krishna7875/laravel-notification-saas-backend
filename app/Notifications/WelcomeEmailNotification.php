<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Welcome Email Notification
 *
 * This notification is sent to new users after registration.
 * It implements ShouldQueue, so it will be queued for background processing.
 *
 * When dispatched, this notification:
 * 1. Gets pushed to the queue
 * 2. A queue worker picks it up
 * 3. The email is sent (either via Mailpit in dev or real mail in production)
 * 4. The user sees registration success immediately without waiting
 */
class WelcomeEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The recipient's name.
     *
     * @var string
     */
    public string $recipientName;

    /**
     * Create a new notification instance.
     *
     * @param string $recipientName The name of the person being welcomed
     */
    public function __construct(string $recipientName)
    {
        $this->recipientName = $recipientName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * This notification will be sent via the 'mail' channel (SMTP).
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * This method defines the email content that will be sent.
     * Mailpit will capture this email in development.
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->greeting("Hello {$this->recipientName}!")
            ->line('Welcome to Laravel Notification SaaS!')
            ->line('Thank you for registering with us.')
            ->line('You can now log in and start using our platform.')
            ->action('Login to Dashboard', url('/'))
            ->line('If you have any questions, feel free to reach out to our support team.')
            ->salutation('Best regards,
Laravel Notification SaaS Team');
    }
}
