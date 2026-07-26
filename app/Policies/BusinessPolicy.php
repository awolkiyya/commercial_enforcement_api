<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Business;

class BusinessPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Business $business): bool
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
        return $user->hasAnyRole(['CITY_ADMIN', 'SUBCITY_ADMIN']);
    }

    public function update(User $user, Business $business): bool
    {
        return $user->hasAnyRole(['CITY_ADMIN', 'SUBCITY_ADMIN']);
    }

    public function delete(User $user): bool
    {
        return $user->hasRole('SUPER_ADMIN');
    }
}