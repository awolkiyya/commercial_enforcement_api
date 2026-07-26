<?php

namespace App\Policies;

use App\Models\User;
use App\Models\City;
use App\Models\SubCity;
use App\Models\Wereda;

class GeoPolicy
{
    public function manage(User $user): bool
    {
        return $user->hasAnyRole(['CITY_ADMIN', 'SUPER_ADMIN']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('SUPER_ADMIN');
    }

    public function update(User $user): bool
    {
        return $user->hasAnyRole(['CITY_ADMIN', 'SUPER_ADMIN']);
    }

    public function delete(User $user): bool
    {
        return $user->hasRole('SUPER_ADMIN');
    }

    public function view(User $user): bool
    {
        return true;
    }
}