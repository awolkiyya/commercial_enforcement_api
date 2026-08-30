<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Maximum failed password attempts before account lockout.
     */
    private const MAX_FAILED_LOGIN_ATTEMPTS = 5;

    /**
     * Account lock duration.
     */
    private const LOCK_DURATION_MINUTES = 10;

    /**
     * Maximum login attempts allowed by the rate limiter.
     *
     * This is independent from the account lockout.
     */
    private const MAX_RATE_LIMIT_ATTEMPTS = 10;

    /**
     * Rate-limit decay in seconds.
     */
    private const RATE_LIMIT_DECAY_SECONDS = 60;

    /**
     * LOGIN
     *
     * Verifies credentials and returns a structured result.
     *
     * Session creation is handled separately by
     * AuthSessionService.
     */
    public function login(array $data, ?string $ipAddress = null): array
    {
        $email = Str::lower(
            trim($data['email'])
        );

        $ipAddress ??= 'unknown';

        /*
        |--------------------------------------------------------------------------
        | RATE LIMIT KEYS
        |--------------------------------------------------------------------------
        |
        | Include both email and IP.
        |
        | This protects against:
        |
        | 1. Excessive attempts against one account.
        | 2. Excessive attempts from one IP.
        |
        */

        $emailKey = 'login:email:' . $email;

        $ipKey = 'login:ip:' . $ipAddress;

        /*
        |--------------------------------------------------------------------------
        | EMAIL RATE LIMIT
        |--------------------------------------------------------------------------
        */

        if (
            RateLimiter::tooManyAttempts(
                $emailKey,
                self::MAX_RATE_LIMIT_ATTEMPTS
            )
        ) {
            $retryAfterSeconds = RateLimiter::availableIn(
                $emailKey
            );

            return [
                'success' => false,
                'code' => 'TOO_MANY_LOGIN_ATTEMPTS',
                'message' => $this->getRateLimitMessage(
                    $retryAfterSeconds
                ),
                'details' => [
                    'retry_after_seconds' => $retryAfterSeconds,

                    'retry_after_minutes' => (int) ceil(
                        $retryAfterSeconds / 60
                    ),
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | IP RATE LIMIT
        |--------------------------------------------------------------------------
        */

        if (
            RateLimiter::tooManyAttempts(
                $ipKey,
                self::MAX_RATE_LIMIT_ATTEMPTS
            )
        ) {
            $retryAfterSeconds = RateLimiter::availableIn(
                $ipKey
            );

            return [
                'success' => false,
                'code' => 'TOO_MANY_LOGIN_ATTEMPTS',
                'message' => $this->getRateLimitMessage(
                    $retryAfterSeconds
                ),
                'details' => [
                    'retry_after_seconds' => $retryAfterSeconds,

                    'retry_after_minutes' => (int) ceil(
                        $retryAfterSeconds / 60
                    ),
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | FIND USER
        |--------------------------------------------------------------------------
        */

        $user = User::with([
            'roles',
            'permissions',
        ])
            ->where('email', $email)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | ACCOUNT LOCK CHECK
        |--------------------------------------------------------------------------
        */

        if ($user && $user->locked_until) {

            if ($user->locked_until->isFuture()) {

                $remainingSeconds = max(
                    0,
                    now()->diffInSeconds(
                        $user->locked_until
                    )
                );

                return [
                    'success' => false,
                    'code' => 'ACCOUNT_TEMPORARILY_LOCKED',
                    'message' => $this->getLockMessage(
                        $remainingSeconds
                    ),
                    'details' => [
                        'locked_until' => $user->locked_until
                            ->toIso8601String(),

                        'remaining_seconds' => $remainingSeconds,

                        'remaining_minutes' => (int) ceil(
                            $remainingSeconds / 60
                        ),
                    ],
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | LOCK EXPIRED
            |--------------------------------------------------------------------------
            */

            $user->update([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | PASSWORD VERIFICATION
        |--------------------------------------------------------------------------
        */

        if (
            !$user ||
            !Hash::check(
                $data['password'],
                $user->password
            )
        ) {

            /*
            |--------------------------------------------------------------------------
            | RECORD RATE-LIMIT FAILURE
            |--------------------------------------------------------------------------
            */

            RateLimiter::hit(
                $emailKey,
                self::RATE_LIMIT_DECAY_SECONDS
            );

            RateLimiter::hit(
                $ipKey,
                self::RATE_LIMIT_DECAY_SECONDS
            );

            /*
            |--------------------------------------------------------------------------
            | DO NOT REVEAL WHETHER EMAIL EXISTS
            |--------------------------------------------------------------------------
            */

            if (!$user) {
                return [
                    'success' => false,
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'The email or password you entered is incorrect.',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | RECORD ACCOUNT FAILURE
            |--------------------------------------------------------------------------
            */

            $this->recordFailedLogin($user);

            $user->refresh();

            /*
            |--------------------------------------------------------------------------
            | ACCOUNT JUST LOCKED
            |--------------------------------------------------------------------------
            */

            if (
                $user->locked_until &&
                $user->locked_until->isFuture()
            ) {

                $remainingSeconds = max(
                    0,
                    now()->diffInSeconds(
                        $user->locked_until
                    )
                );

                return [
                    'success' => false,
                    'code' => 'ACCOUNT_TEMPORARILY_LOCKED',
                    'message' => $this->getLockMessage(
                        $remainingSeconds
                    ),
                    'details' => [
                        'failed_attempts' =>
                            $user->failed_login_attempts,

                        'max_attempts' =>
                            self::MAX_FAILED_LOGIN_ATTEMPTS,

                        'locked_until' =>
                            $user->locked_until->toIso8601String(),

                        'remaining_seconds' =>
                            $remainingSeconds,

                        'remaining_minutes' =>
                            (int) ceil(
                                $remainingSeconds / 60
                            ),
                    ],
                ];
            }

            return [
                'success' => false,
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'The email or password you entered is incorrect.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | ACCOUNT STATUS
        |--------------------------------------------------------------------------
        */

        if (!$user->is_active) {

            return [
                'success' => false,
                'code' => 'ACCOUNT_INACTIVE',
                'message' => 'Your account is inactive. Please contact your administrator.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | SUCCESSFUL LOGIN
        |--------------------------------------------------------------------------
        */

        RateLimiter::clear($emailKey);

        /*
        | Don't clear the IP limiter completely.
        |
        | The IP limiter protects against attacks against multiple accounts.
        */

        $user->update([
            'last_login_at' => now(),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | RETURN USER
        |--------------------------------------------------------------------------
        */

        return [
            'success' => true,
            'user' => $user->fresh()->load([
                'roles',
                'permissions',
            ]),
        ];
    }

    /**
     * Record failed login attempt.
     */
    protected function recordFailedLogin(User $user): void
    {
        $attempts = $user->failed_login_attempts + 1;

        /*
        |--------------------------------------------------------------------------
        | LOCK ACCOUNT
        |--------------------------------------------------------------------------
        */

        if ($attempts >= self::MAX_FAILED_LOGIN_ATTEMPTS) {

            $user->update([
                'failed_login_attempts' => $attempts,

                'locked_until' => now()->addMinutes(
                    self::LOCK_DURATION_MINUTES
                ),
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE FAILED ATTEMPTS
        |--------------------------------------------------------------------------
        */

        $user->update([
            'failed_login_attempts' => $attempts,
        ]);
    }

    /**
     * Generate human-readable rate-limit message.
     */
    protected function getRateLimitMessage(
        int $remainingSeconds
    ): string {

        if ($remainingSeconds <= 0) {
            return 'You can try logging in again now.';
        }

        if ($remainingSeconds < 60) {

            return sprintf(
                'Too many login attempts. Please try again in %d %s.',
                $remainingSeconds,
                $remainingSeconds === 1
                    ? 'second'
                    : 'seconds'
            );
        }

        $minutes = intdiv(
            $remainingSeconds,
            60
        );

        $seconds = $remainingSeconds % 60;

        if ($seconds > 0) {

            return sprintf(
                'Too many login attempts. Please try again in %d %s and %d %s.',
                $minutes,
                $minutes === 1
                    ? 'minute'
                    : 'minutes',
                $seconds,
                $seconds === 1
                    ? 'second'
                    : 'seconds'
            );
        }

        return sprintf(
            'Too many login attempts. Please try again in %d %s.',
            $minutes,
            $minutes === 1
                ? 'minute'
                : 'minutes'
        );
    }

    /**
     * Generate human-readable account lock message.
     */
    protected function getLockMessage(
        int $remainingSeconds
    ): string {

        if ($remainingSeconds <= 0) {
            return 'Your account lock has expired. You can try logging in again.';
        }

        if ($remainingSeconds < 60) {

            return sprintf(
                'Your account is temporarily locked. Please try again in %d %s.',
                $remainingSeconds,
                $remainingSeconds === 1
                    ? 'second'
                    : 'seconds'
            );
        }

        $minutes = intdiv(
            $remainingSeconds,
            60
        );

        $seconds = $remainingSeconds % 60;

        if ($minutes < 60) {

            if ($seconds > 0) {

                return sprintf(
                    'Your account is temporarily locked. Please try again in %d %s and %d %s.',
                    $minutes,
                    $minutes === 1
                        ? 'minute'
                        : 'minutes',
                    $seconds,
                    $seconds === 1
                        ? 'second'
                        : 'seconds'
                );
            }

            return sprintf(
                'Your account is temporarily locked. Please try again in %d %s.',
                $minutes,
                $minutes === 1
                    ? 'minute'
                    : 'minutes'
            );
        }

        $hours = intdiv(
            $minutes,
            60
        );

        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes > 0) {

            return sprintf(
                'Your account is temporarily locked. Please try again in %d %s and %d %s.',
                $hours,
                $hours === 1
                    ? 'hour'
                    : 'hours',
                $remainingMinutes,
                $remainingMinutes === 1
                    ? 'minute'
                    : 'minutes'
            );
        }

        return sprintf(
            'Your account is temporarily locked. Please try again in %d %s.',
            $hours,
            $hours === 1
                ? 'hour'
                : 'hours'
        );
    }

    /**
     * UPDATE PASSWORD
     */
    public function updatePassword(
        User $user,
        array $data
    ): array {

        if (!Hash::check(
            $data['current_password'],
            $user->password
        )) {
            return [
                'success' => false,
                'code' => 'INVALID_CURRENT_PASSWORD',
                'message' => 'The current password you entered is incorrect.',
            ];
        }

        $user->update([
            'password' => Hash::make(
                $data['new_password']
            ),
        ]);

        return [
            'success' => true,
            'message' => 'Your password has been updated successfully.',
        ];
    }

    /**
     * UPDATE PROFILE
     */
    public function updateProfile(
        User $user,
        array $data
    ): array {

        try {

            $user->update([
                'name' => $data['name'],
                'email' => Str::lower(
                    trim($data['email'])
                ),
            ]);

            return [
                'success' => true,
                'message' => 'Your profile has been updated successfully.',
                'user' => $user->fresh(),
            ];

        } catch (\Throwable $e) {

            report($e);

            return [
                'success' => false,
                'code' => 'PROFILE_UPDATE_FAILED',
                'message' => 'Unable to update your profile. Please try again.',
            ];
        }
    }
}
