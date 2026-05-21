<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendNotificationRequest;
use App\Http\Responses\ApiResponse;
use App\Notifications\WelcomeEmailNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\AnonymousNotifiable;

/**
 * Notification Demo Controller
 *
 * This controller demonstrates how to dispatch queued notifications.
 * In production, notifications would be sent from various places:
 * - AuthController after registration
 * - SubscriptionController after payment
 * - WebhookController after external events
 */
class NotificationDemoController extends Controller
{
    /**
     * Send a welcome email notification to a recipient.
     *
     * This endpoint demonstrates async email sending:
     * 1. Notification is queued immediately
     * 2. API response returned to user instantly
     * 3. Queue worker sends email in background
     * 4. Email appears in Mailpit or goes to real mailbox
     *
     * In development, emails go to Mailpit. In production, they go to real addresses.
     */
    public function sendWelcomeEmail(SendNotificationRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Create an anonymous notifiable (for testing without a User model)
        // In production, you'd pass an actual User object
        $notifiable = (new AnonymousNotifiable())
            ->route('mail', $data['email']);

        // Dispatch the queued notification
        // This immediately returns and queues the job
        $notifiable->notify(new WelcomeEmailNotification($data['name']));

        return ApiResponse::success([
            'notification_queued' => true,
            'recipient' => $data['email'],
            'message' => 'Check Mailpit at http://127.0.0.1:8025',
        ], 'Welcome notification has been queued for delivery.');
    }
}
