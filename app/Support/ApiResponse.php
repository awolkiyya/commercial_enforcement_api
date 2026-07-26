<?php

namespace App\Support;

use Illuminate\Support\Str;

class ApiResponse
{
    /**
     * Base JSON response builder
     */
    private static function base(array $payload, int $status)
    {
        return response()->json($payload, $status);
    }

    /**
     * Get or generate request ID (should ideally be set by middleware)
     */
    private static function requestId(): string
    {
        return request()->attributes->get('request_id')
            ?? request()->header('X-Request-ID')
            ?? (string) Str::uuid();
    }

    /**
     * Success response
     */
    public static function success($data = null, string $message = 'Success', array $meta = [])
    {
        return self::base([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
            'request_id' => self::requestId(),
            'timestamp' => now()->toISOString(),
        ], 200);
    }

    /**
     * Created response
     */
    public static function created($data = null, string $message = 'Created successfully')
    {
        return self::base([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'request_id' => self::requestId(),
            'timestamp' => now()->toISOString(),
        ], 201);
    }

    /**
     * Generic error response
     */
    public static function error(
        string $message = 'Unexpected error occurred',
        $errors = [],
        int $code = 500
    ) {
        return self::base([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'request_id' => self::requestId(),
            'timestamp' => now()->toISOString(),
        ], $code);
    }

    /**
     * Unauthorized (401)
     */
    public static function unauthorized(string $message = 'Unauthorized')
    {
        return self::error($message, [], 401);
    }

    /**
     * Forbidden (403)
     */
    public static function forbidden(string $message = 'Forbidden')
    {
        return self::error($message, [], 403);
    }

    /**
     * Not found (404)
     */
    public static function notFound(string $message = 'Not found')
    {
        return self::error($message, [], 404);
    }

    /**
     * Validation error (422)
     */
    public static function validation($errors, string $message = 'Validation error')
    {
        return self::error($message, $errors, 422);
    }
}