<?php

namespace App\Policies;

use App\Models\SubCity;
use App\Models\User;
use App\Policies\Concerns\ChecksHierarchy;

class SubCityPolicy
{
    use ChecksHierarchy;

    /**
     * =====================================================
     * VIEW LIST
     * =====================================================
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('SUPER_ADMIN')
            || $user->hasRole('ADMIN');
    }

    /**
     * =====================================================
     * VIEW SINGLE SUBCITY
     * =====================================================
     */
    public function view(User $user, SubCity $subcity): bool
    {
        if ($user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        // ADMIN can view only subcities in their city
        if ($user->hasRole('ADMIN')) {
            return $this->sameCity($user, $subcity);
        }

        // SUPERVISOR (if assigned to subcity level)
        if ($user->hasRole('SUPERVISOR')) {
            return $this->sameSubCity($user, $subcity);
        }

        return false;
    }

    /**
     * =====================================================
     * CREATE SUBCITY
     * =====================================================
     */
    public function create(User $user): bool
    {
        return $user->hasRole('SUPER_ADMIN')
            || $user->hasRole('ADMIN');
    }

    /**
     * =====================================================
     * UPDATE SUBCITY
     * =====================================================
     */
    public function update(User $user, SubCity $subcity): bool
    {
        if ($user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if ($user->hasRole('ADMIN')) {
            return $this->sameCity($user, $subcity);
        }

        return false;
    }

    /**
     * =====================================================
     * DELETE SUBCITY
     * =====================================================
     */
    public function delete(User $user, SubCity $subcity): bool
    {
        return $user->hasRole('SUPER_ADMIN');
    }
}