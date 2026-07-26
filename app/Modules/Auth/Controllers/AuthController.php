<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Resources\LoginResource;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;


class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * =========================
     * LOGIN
     * =========================
     */
    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());
    
        if (!$result) {
            return ApiResponse::validation(
                [
                    'email' => ['Invalid email or password']
                ],
                'Invalid credentials'
            );
        }
    
        $user = $result['user'];
        $token = $result['token'];
    
        $primaryRole = $user->roles->first()?->name;
    
        return ApiResponse::success(
            new LoginResource($user, $token),
            'Login successful'
        )->withCookie(
            cookie('token', $token, 60 * 24, '/', null, false, true, false, 'Strict')
        )->withCookie(
            cookie('role', $primaryRole, 60 * 24, '/', null, false, false, false, 'Strict')
        );
    }

    /**
     * =========================
     * CURRENT USER
     * =========================
     */
    public function me(Request $request)
    {
        return ApiResponse::success(
            new LoginResource(
                $request->user()->load(['roles', 'permissions']),
                $request->user()->currentAccessToken()?->token ?? null
            ),
            'User profile'
        );
    }

    /**
     * =========================
     * LOGOUT
     * =========================
     */
    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return ApiResponse::success(null, 'Logged out successfully');
    }
}