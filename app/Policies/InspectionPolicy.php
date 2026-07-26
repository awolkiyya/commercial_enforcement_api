<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Inspection;

class InspectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'SUPER_ADMIN',
            'CITY_ADMIN',
            'SUBCITY_ADMIN',
            'WEREDA_ADMIN',
            'INSPECTOR'
        ]);
    }

    public function view(User $user, Inspection $inspection): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('INSPECTOR');
    }

    public function update(User $user, Inspection $inspection): bool
    {
        return $user->hasAnyRole(['WEREDA_ADMIN', 'SUBCITY_ADMIN', 'CITY_ADMIN']);
    }

    public function assign(User $user): bool
    {
        return $user->hasAnyRole(['WEREDA_ADMIN', 'SUBCITY_ADMIN', 'CITY_ADMIN']);
    }

    public function delete(User $user): bool
    {
        return $user->hasRole('SUPER_ADMIN');
    }
}