<?php

namespace App\Policies;

use App\Models\Wereda;
use App\Models\User;
use App\Policies\Concerns\ChecksHierarchy;

class WeredaPolicy
{
    use ChecksHierarchy;

    /**
     * =====================================================
     * LIST ALL WEREDAS
     * =====================================================
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('SUPER_ADMIN')
            || $user->hasRole('ADMIN')
            || $user->hasRole('SUPERVISOR');
    }

    /**
     * =====================================================
     * VIEW SINGLE WEREDA
     * =====================================================
     */
    public function view(User $user, Wereda $wereda): bool
    {
        if ($user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if ($user->hasRole('ADMIN')) {
            return $this->sameCity($user, $wereda);
        }

        if ($user->hasRole('SUPERVISOR')) {
            return $this->sameSubcity($user, $wereda);
        }

        if ($user->hasRole('INSPECTOR')) {
            return $this->sameWereda($user, $wereda);
        }

        return false;
    }

    /**
     * =====================================================
     * CREATE WEREDA
     * =====================================================
     */
    public function create(User $user): bool
    {
        return $user->hasRole('SUPER_ADMIN')
            || $user->hasRole('ADMIN');
    }

    /**
     * =====================================================
     * UPDATE WEREDA
     * =====================================================
     */
    public function update(User $user, Wereda $wereda): bool
    {
        if ($user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if ($user->hasRole('ADMIN')) {
            return $this->sameCity($user, $wereda);
        }

        return false;
    }

    /**
     * =====================================================
     * DELETE WEREDA
     * =====================================================
     */
    public function delete(User $user, Wereda $wereda): bool
    {
        return $user->hasRole('SUPER_ADMIN');
    }
}