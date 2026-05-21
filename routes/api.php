<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationDemoController;
use App\Http\Controllers\QueueDemoController;
use App\Http\Controllers\SmsDemoController;
use App\Http\Controllers\SubscriptionPlanController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected auth routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});

// Queue demo route
Route::post('/demo/welcome-email', [QueueDemoController::class, 'sendWelcomeEmail']);

// Notification demo route
Route::post('/demo/send-notification', [NotificationDemoController::class, 'sendWelcomeEmail']);

// SMS demo routes
Route::post('/demo/send-sms', [SmsDemoController::class, 'sendSms']);
Route::post('/demo/send-welcome-sms', [SmsDemoController::class, 'sendWelcomeSms']);

// Public subscription plan routes
Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index']);
Route::get('/subscription-plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'show']);

// Protected subscription plan routes (admin only)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/subscription-plans', [SubscriptionPlanController::class, 'store']);
    Route::put('/subscription-plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'update']);
    Route::delete('/subscription-plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'destroy']);
});

Route::get('/test', function () {
    return response()->json([
        'status' => true,
        'message' => 'API working successfully',
        'data' => null,
        'errors' => null,
    ]);
});