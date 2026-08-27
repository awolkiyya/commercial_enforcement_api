<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Resources\LoginResource;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\AuthSessionService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected AuthSessionService $authSessionService,
    ) {}
/**
 * =========================
 * LOGIN
 * =========================
 */
public function login(LoginRequest $request)
{
    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATE
    |--------------------------------------------------------------------------
    */

    $result = $this->authService->login(
        $request->validated()
    );

    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATION FAILED
    |--------------------------------------------------------------------------
    */

    if (!$result['success']) {
        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'code' => $result['code'] ?? 'AUTHENTICATION_FAILED',
            'details' => $result['details'] ?? null,
        ], 401);
    }

    /*
    |--------------------------------------------------------------------------
    | GET AUTHENTICATED USER
    |--------------------------------------------------------------------------
    */

    $user = $result['user'];

    /*
    |--------------------------------------------------------------------------
    | REMEMBER ME
    |--------------------------------------------------------------------------
    */

    $remember = $request->boolean('remember');

    /*
    |--------------------------------------------------------------------------
    | CREATE LARAVEL WEB SESSION
    |--------------------------------------------------------------------------
    */

    $this->authSessionService->authenticate(
        $request,
        $user,
        $remember
    );

    /*
    |--------------------------------------------------------------------------
    | LOAD AUTHORIZATION DATA
    |--------------------------------------------------------------------------
    |
    | Spatie roles and permissions are loaded from the database.
    | These remain the server-side source of truth.
    |
    */

    $user->load([
        'roles',
        'permissions',
    ]);

    /*
    |--------------------------------------------------------------------------
    | PRIMARY ROLE
    |--------------------------------------------------------------------------
    */

    $primaryRole = $user->roles->first()?->name;

    /*
    |--------------------------------------------------------------------------
    | LOGIN RESPONSE
    |--------------------------------------------------------------------------
    */

    return ApiResponse::success(
        new LoginResource($user),
        'Login successful'
    )->withCookie(
        cookie(
            'role',

            // Exact role assigned by Spatie
            $primaryRole,

            // Lifetime
            $remember
                ? 60 * 24 * 30   // 30 days
                : 60 * 24,       // 24 hours

            // Path
            '/',

            // Domain
            null,

            // Secure
            app()->environment('production'),

            // HttpOnly
            false,

            // Raw
            false,

            // SameSite
            'Lax'
        )
    );
}

    /**
     * =========================
     * CURRENT USER
     * =========================
     */
    public function me(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | GET AUTHENTICATED USER
        |--------------------------------------------------------------------------
        */

        $user = $request->user('web');

        /*
        |--------------------------------------------------------------------------
        | NOT AUTHENTICATED
        |--------------------------------------------------------------------------
        */

        if (!$user) {
            return ApiResponse::unauthorized(
                'Unauthenticated.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | LOAD AUTHORIZATION DATA
        |--------------------------------------------------------------------------
        */

        $user->load([
            'roles',
            'permissions',
        ]);

        /*
        |--------------------------------------------------------------------------
        | RETURN USER
        |--------------------------------------------------------------------------
        */

        return ApiResponse::success(
            new LoginResource($user),
            'User profile.'
        );
    }

    /**
     * =========================
     * LOGOUT
     * =========================
     */
    public function logout(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | DESTROY WEB SESSION
        |--------------------------------------------------------------------------
        */

        $this->authSessionService->logout(
            $request
        );

        return ApiResponse::success(
            null,
            'Logged out successfully.'
        );
    }
}