<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendWelcomeEmailRequest;
use App\Http\Responses\ApiResponse;
use App\Jobs\SendWelcomeEmailJob;
use Illuminate\Http\JsonResponse;

class QueueDemoController extends Controller
{
    /**
     * Dispatch a sample welcome email job to the queue.
     *
     * This endpoint demonstrates how to use Laravel queues for background work.
     */
    public function sendWelcomeEmail(SendWelcomeEmailRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Dispatch the job to the queue. This returns immediately.
        SendWelcomeEmailJob::dispatch($data['name'], $data['email']);

        return ApiResponse::success([
            'queued' => true,
            'email' => $data['email'],
        ], 'Welcome email job dispatched. Check the log for execution details.');
    }
}
