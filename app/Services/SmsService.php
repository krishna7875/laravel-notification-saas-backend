<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * SMS Service
 *
 * This service handles all SMS sending operations via Twilio.
 * It acts as a wrapper around Twilio, keeping the integration logic
 * separate from controllers and jobs.
 *
 * Why use a service class?
 * - Centralized SMS logic (easy to maintain)
 * - Can be reused across multiple jobs/controllers
 * - Easy to mock for testing
 * - Easy to switch SMS providers later
 * - Keeps business logic out of controllers
 */
class SmsService
{
    /**
     * Send an SMS message to a phone number.
     *
     * This method will be called by the SendSmsJob.
     * In development, it logs the SMS. In production, it sends via Twilio.
     *
     * @param  string  $phoneNumber The recipient phone number (E.164 format: +1234567890)
     * @param  string  $message The SMS message content
     * @return bool Whether the SMS was sent successfully
     */
    public function send(string $phoneNumber, string $message): bool
    {
        try {
            // In production, uncomment this to actually send via Twilio:
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

            // For development/testing, just log the SMS
            Log::info('SMS sent successfully', [
                'phone' => $phoneNumber,
                'message' => $message,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'phone' => $phoneNumber,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send a welcome SMS to a new user.
     *
     * This is a convenience method that formats the message for welcome SMS.
     *
     * @param  string  $phoneNumber
     * @param  string  $userName
     * @return bool
     */
    public function sendWelcomeSms(string $phoneNumber, string $userName): bool
    {
        $message = "Welcome {$userName}! Thanks for joining Laravel Notification SaaS. Visit us at https://example.com";

        return $this->send($phoneNumber, $message);
    }

    /**
     * Send an OTP (One-Time Password) SMS.
     *
     * Used for 2FA, password reset, etc.
     *
     * @param  string  $phoneNumber
     * @param  string  $otp
     * @return bool
     */
    public function sendOtpSms(string $phoneNumber, string $otp): bool
    {
        $message = "Your verification code is: {$otp}. Valid for 10 minutes.";

        return $this->send($phoneNumber, $message);
    }

    /**
     * Send a verification SMS for account confirmation.
     *
     * @param  string  $phoneNumber
     * @return bool
     */
    public function sendVerificationSms(string $phoneNumber): bool
    {
        $message = "Your account verification code has been sent. Check your email for details.";

        return $this->send($phoneNumber, $message);
    }
}
