<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * LOGIN
     */
    public function login(array $data): ?array
    {
        $user = User::with(['roles', 'permissions'])
            ->where('email', $data['email'])
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return null;
        }

        // revoke all existing tokens (single-session security model)
        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * REFRESH TOKEN
     */
    public function refresh(User $user): string
    {
        // rotate token (safe refresh pattern)
        $user->tokens()->delete();

        return $user->createToken('api-token')->plainTextToken;
    }

    /**
     * LOGOUT (single session model)
     */
    public function logout(User $user): void
    {
        // consistent with login (full revoke)
        $user->tokens()->delete();
    }
}