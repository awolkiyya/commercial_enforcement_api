<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Violation;
use App\Enums\ViolationStatus;

class ViolationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Violation $violation): bool
    {
        return $user->hasAnyRole([
            'SUPER_ADMIN',
            'CITY_ADMIN',
            'SUBCITY_ADMIN',
            'WEREDA_ADMIN',
            'INSPECTOR'
        ]);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('INSPECTOR');
    }

    public function submit(User $user, Violation $violation): bool
    {
        return $user->hasRole('INSPECTOR')
            && $violation->status === ViolationStatus::DETECTED;
    }

    public function review(User $user, Violation $violation): bool
    {
        return $user->hasAnyRole(['WEREDA_ADMIN', 'SUBCITY_ADMIN', 'CITY_ADMIN']);
    }

    public function approve(User $user, Violation $violation): bool
    {
        return $user->hasAnyRole(['WEREDA_ADMIN', 'CITY_ADMIN', 'SUPER_ADMIN']);
    }

    public function reject(User $user, Violation $violation): bool
    {
        return $user->hasAnyRole(['WEREDA_ADMIN', 'CITY_ADMIN', 'SUPER_ADMIN']);
    }

    public function penalize(User $user, Violation $violation): bool
    {
        return $user->hasAnyRole(['CITY_ADMIN', 'SUPER_ADMIN']);
    }

    public function close(User $user, Violation $violation): bool
    {
        return $user->hasRole('SUPER_ADMIN');
    }

    public function delete(User $user, Violation $violation): bool
    {
        return $user->hasRole('SUPER_ADMIN');
    }
}