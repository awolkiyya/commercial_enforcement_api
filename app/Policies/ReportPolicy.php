<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Report;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Report $report): bool
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
        return $user->hasAnyRole(['INSPECTOR', 'WEREDA_ADMIN']);
    }

    public function approve(User $user): bool
    {
        return $user->hasAnyRole(['CITY_ADMIN', 'SUPER_ADMIN']);
    }

    public function delete(User $user): bool
    {
        return $user->hasRole('SUPER_ADMIN');
    }
}