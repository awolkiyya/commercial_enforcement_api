<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthSessionService
{
    /**
     * Authenticate the user using Laravel's web session.
     */
    public function authenticate(
        Request $request,
        User $user
    ): void {
        Auth::guard('web')->login($user, false);

        // Prevent session fixation.
        $request->session()->regenerate();
    }

    /**
     * Check whether the current web session is authenticated.
     */
    public function check(): bool
    {
        return Auth::guard('web')->check();
    }

    /**
     * Get the authenticated web user.
     */
    public function user(): ?User
    {
        return Auth::guard('web')->user();
    }

    /**
     * Get authenticated user ID.
     */
    public function userId(): mixed
    {
        return Auth::guard('web')->id();
    }

    /**
     * Logout the current web session.
     */
    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();
    }
}