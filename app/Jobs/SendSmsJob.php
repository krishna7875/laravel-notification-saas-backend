<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Send SMS Job
 *
 * This job sends an SMS message asynchronously via the queue.
 *
 * Why queue SMS?
 * - SMS API calls are slow (external HTTP requests to Twilio)
 * - User doesn't need to wait for SMS delivery
 * - If SMS fails, the job can be retried automatically
 * - Multiple SMS can be sent in parallel by multiple workers
 *
 * Workflow:
 * 1. Controller calls SendSmsJob::dispatch($phone, $message)
 * 2. Job is stored in the queue
 * 3. Queue worker picks it up
 * 4. Job calls SmsService::send()
 * 5. SMS sent to Twilio
 * 6. If successful, job removed from queue
 * 7. If failed, job retried (configurable)
 */
class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The phone number to send SMS to.
     *
     * Should be in E.164 format: +1234567890
     *
     * @var string
     */
    public string $phoneNumber;

    /**
     * The SMS message content.
     *
     * @var string
     */
    public string $message;

    /**
     * Create a new job instance.
     *
     * @param  string  $phoneNumber The recipient phone number
     * @param  string  $message The SMS message to send
     */
    public function __construct(string $phoneNumber, string $message)
    {
        $this->phoneNumber = $phoneNumber;
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * This method is called by the queue worker.
     * If it throws an exception, the job will be retried.
     * After max retries, it goes to failed_jobs table.
     */
    public function handle(SmsService $smsService): void
    {
        // Use dependency injection - Laravel automatically instantiates SmsService
        $smsService->send($this->phoneNumber, $this->message);
    }

    /**
     * Handle job failure.
     *
     * This is called if the job fails after all retries.
     * Use this to log, alert, or store failure data.
     */
    public function failed(\Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error('SendSmsJob failed permanently', [
            'phone' => $this->phoneNumber,
            'message' => $this->message,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Configure job retry behavior.
     *
     * $tries: How many times to attempt
     * $backoff: Wait time (seconds) before each retry
     */
    public int $tries = 3;
    public array $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min
}
