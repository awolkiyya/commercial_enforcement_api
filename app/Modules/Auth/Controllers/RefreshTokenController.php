<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Auth\Services\AuthService;
use App\Support\ApiResponse;

class RefreshTokenController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * =========================================
     * REFRESH TOKEN (SANCTUM SAFE VERSION)
     * =========================================
     */
    public function refresh(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return ApiResponse::unauthorized('Unauthenticated user');
        }

        // rotate token (safe refresh pattern)
        $token = $this->authService->refresh($user);

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Token refreshed successfully');
    }
}