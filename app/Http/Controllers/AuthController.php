<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Responses\ApiResponse;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService)
    {
    }

    /**
     * Register a new user and return an API token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return ApiResponse::success([
            'user' => $result['user'],
            'token' => $result['token'],
        ], 'Registration successful.', 201);
    }

    /**
     * Login and return a new API token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return ApiResponse::success([
            'user' => $result['user'],
            'token' => $result['token'],
        ], 'Login successful.');
    }

    /**
     * Revoke the current auth token.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return ApiResponse::success(null, 'Logged out successfully.');
    }

    /**
     * Return the authenticated user details.
     */
    public function user(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'user' => $request->user(),
        ], 'Authenticated user returned.');
    }
}
