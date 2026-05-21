<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendSmsRequest;
use App\Http\Responses\ApiResponse;
use App\Jobs\SendSmsJob;
use Illuminate\Http\JsonResponse;

/**
 * SMS Demo Controller
 *
 * This controller demonstrates queued SMS sending.
 *
 * Key principle: Keep controllers thin!
 * - Controller only handles: validation, dispatching, response
 * - All SMS logic lives in SmsService
 * - All async work lives in SendSmsJob
 */
class SmsDemoController extends Controller
{
    /**
     * Send an SMS message.
     *
     * This endpoint:
     * 1. Validates the request (phone, message)
     * 2. Queues the SendSmsJob
     * 3. Returns success immediately
     * 4. Queue worker sends SMS in background
     *
     * API Flow (Async):
     * Request → Validate → Queue Job → Return 200 OK
     * Meanwhile: Worker picks up job → Calls SmsService → SMS sent
     *
     * @param  \App\Http\Requests\SendSmsRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendSms(SendSmsRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Queue the SMS job for background processing
        // This returns immediately, doesn't wait for SMS delivery
        SendSmsJob::dispatch($data['phone_number'], $data['message']);

        return ApiResponse::success([
            'queued' => true,
            'phone_number' => $data['phone_number'],
            'message' => 'SMS has been queued. Check logs for delivery status.',
        ], 'SMS queued successfully for delivery.', 202);
    }

    /**
     * Send a welcome SMS to a new user.
     *
     * Convenience endpoint for welcome messages.
     * In production, this would be called from AuthController after registration.
     */
    public function sendWelcomeSms(SendSmsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $message = "Welcome! Thanks for joining Laravel Notification SaaS. Visit https://example.com";

        SendSmsJob::dispatch($data['phone_number'], $message);

        return ApiResponse::success([
            'queued' => true,
            'phone_number' => $data['phone_number'],
        ], 'Welcome SMS queued successfully.', 202);
    }
}
