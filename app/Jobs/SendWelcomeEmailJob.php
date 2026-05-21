<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * A simple queued job that simulates sending a welcome email.
 *
 * This job writes log messages and waits for a short time to show
 * that the work happens in the background.
 */
class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The recipient name.
     *
     * @var string
     */
    public string $name;

    /**
     * The recipient email address.
     *
     * @var string
     */
    public string $email;

    /**
     * Create a new job instance.
     */
    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('SendWelcomeEmailJob started', [
            'name' => $this->name,
            'email' => $this->email,
        ]);

        // Simulate the background processing work.
        sleep(2);

        Log::info('SendWelcomeEmailJob completed', [
            'name' => $this->name,
            'email' => $this->email,
        ]);
    }
}
