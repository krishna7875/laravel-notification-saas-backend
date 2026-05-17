<?php

use App\Http\Controllers\AuthController;
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