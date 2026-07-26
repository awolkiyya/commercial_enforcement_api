<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        /**
         * =========================================
         * JSON REQUEST DETECTION
         * =========================================
         */
        $isApi = fn ($request) =>
            $request->expectsJson() ||
            $request->wantsJson() ||
            $request->isJson();

        /**
         * =========================================
         * VALIDATION ERROR (422)
         * =========================================
         */
        $this->renderable(function (ValidationException $e, $request) use ($isApi) {
            if (!$isApi($request)) return null;

            return \App\Support\ApiResponse::validation(
                $e->errors(),
                'Validation error'
            );
        });

        /**
         * =========================================
         * UNAUTHENTICATED (401)
         * =========================================
         */
        $this->renderable(function (AuthenticationException $e, $request) use ($isApi) {
            if (!$isApi($request)) return null;

            return \App\Support\ApiResponse::unauthorized(
                'Unauthenticated'
            );
        });

        /**
         * =========================================
         * FORBIDDEN (403)
         * =========================================
         */
        $this->renderable(function (AuthorizationException $e, $request) use ($isApi) {
            if (!$isApi($request)) return null;

            return \App\Support\ApiResponse::forbidden(
                'Forbidden'
            );
        });

        /**
         * =========================================
         * NOT FOUND (404)
         * =========================================
         */
        $this->renderable(function (NotFoundHttpException $e, $request) use ($isApi) {
            if (!$isApi($request)) return null;

            return \App\Support\ApiResponse::notFound(
                'Resource not found'
            );
        });

        /**
         * =========================================
         * DATABASE ERRORS (QUERY EXCEPTIONS)
         * =========================================
         */
        $this->renderable(function (QueryException $e, $request) use ($isApi) {
            if (!$isApi($request)) return null;

            $sqlState = $e->errorInfo[0] ?? null;

            return match ($sqlState) {

                /**
                 * UNIQUE VIOLATION
                 */
                '23505' => \App\Support\ApiResponse::error(
                    'Duplicate entry detected',
                    [],
                    409
                ),

                /**
                 * FOREIGN KEY VIOLATION
                 */
                '23503' => \App\Support\ApiResponse::error(
                    'Related record not found',
                    [],
                    409
                ),

                /**
                 * NOT NULL VIOLATION
                 */
                '23502' => \App\Support\ApiResponse::error(
                    'Missing required field',
                    [],
                    422
                ),

                default => \App\Support\ApiResponse::error(
                    app()->environment('production')
                        ? 'Database error'
                        : $e->getMessage(),
                    [],
                    500
                ),
            };
        });

        /**
         * =========================================
         * GLOBAL FALLBACK LOGGER (IMPORTANT)
         * =========================================
         */
        $this->reportable(function (Throwable $e) {
            logger()->error('Unhandled exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        });
    }

    /**
     * =========================================
     * FINAL SAFETY NET (THIS FIXES YOUR SQL LEAK)
     * =========================================
     */
    public function render($request, Throwable $e)
    {
        $isApi = $request->expectsJson() || $request->wantsJson() || $request->isJson();

        if ($isApi) {

            /**
             * DOUBLE SAFETY: QueryException fallback
             */
            if ($e instanceof QueryException) {

                $sqlState = $e->errorInfo[0] ?? null;

                if ($sqlState === '23505') {
                    return \App\Support\ApiResponse::error(
                        'Duplicate entry detected',
                        [],
                        409
                    );
                }

                if ($sqlState === '23503') {
                    return \App\Support\ApiResponse::error(
                        'Related record not found',
                        [],
                        409
                    );
                }

                return \App\Support\ApiResponse::error(
                    'Database error',
                    [],
                    500
                );
            }

            /**
             * FINAL SAFE FALLBACK (NO RAW SQL EVER)
             */
            return \App\Support\ApiResponse::error(
                app()->environment('production')
                    ? 'Server error'
                    : $e->getMessage(),
                [],
                500
            );
        }

        return parent::render($request, $e);
    }
}