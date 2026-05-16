<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Standard API response helper for JSON APIs.
 *
 * This class keeps response shape consistent across controllers,
 * services, and jobs.
 */
class ApiResponse
{
    /**
     * Return a standard success JSON response.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public static function success(mixed $data = null, string $message = 'Request completed successfully.', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $statusCode);
    }

    /**
     * Return a standard error JSON response.
     *
     * @param  string  $message
     * @param  array|string|null  $errors
     * @param  int  $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public static function error(string $message = 'An error occurred.', array|string|null $errors = null, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $statusCode);
    }
}
