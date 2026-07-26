<?php

namespace App\Policies;

use App\Models\City;
use App\Models\User;
use App\Policies\Concerns\ChecksHierarchy;

class CityPolicy
{
    use ChecksHierarchy;

    /**
     * =====================================================
     * VIEW ANY CITIES
     * =====================================================
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('SUPER_ADMIN')
            || $user->hasRole('ADMIN');
    }

    /**
     * =====================================================
     * VIEW SINGLE CITY
     * =====================================================
     */
    public function view(User $user, City $city): bool
    {
        if ($user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        // CITY level access only to their assigned city
        return $user->city_id === $city->id;
    }

    /**
     * =====================================================
     * CREATE CITY
     * =====================================================
     */
    public function create(User $user): bool
    {
        return $user->hasRole('SUPER_ADMIN');
    }

    /**
     * =====================================================
     * UPDATE CITY
     * =====================================================
     */
    public function update(User $user, City $city): bool
    {
        return $user->hasRole('SUPER_ADMIN');
    }

    /**
     * =====================================================
     * DELETE CITY
     * =====================================================
     */
    public function delete(User $user, City $city): bool
    {
        return $user->hasRole('SUPER_ADMIN');
    }
}