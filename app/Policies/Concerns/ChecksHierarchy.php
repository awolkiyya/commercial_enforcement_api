<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksHierarchy
{
    /*
    |--------------------------------------------------------------------------
    | ROLE CHECKS
    |--------------------------------------------------------------------------
    */

    protected function isSystemAdmin(User $user): bool
    {
        return $user->hasRole('SUPER_ADMIN');
    }

    protected function isAdmin(User $user): bool
    {
        return $user->hasRole('ADMIN');
    }

    protected function isSupervisor(User $user): bool
    {
        return $user->hasRole('SUPERVISOR');
    }

    protected function isInspector(User $user): bool
    {
        return $user->hasRole('INSPECTOR');
    }

    /*
    |--------------------------------------------------------------------------
    | GOVERNANCE SCOPE MATCHING (UUID SAFE)
    |--------------------------------------------------------------------------
    */

    protected function sameCity(User $user, $model): bool
    {
        return !empty($user->city_id)
            && !empty($model->city_id)
            && $user->city_id === $model->city_id;
    }

    protected function sameSubcity(User $user, $model): bool
    {
        return !empty($user->subcity_id)
            && !empty($model->subcity_id)
            && $user->subcity_id === $model->subcity_id;
    }

    protected function sameWereda(User $user, $model): bool
    {
        return !empty($user->wereda_id)
            && !empty($model->wereda_id)
            && $user->wereda_id === $model->wereda_id;
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESS BY LEVEL
    |--------------------------------------------------------------------------
    */

    protected function hasAccessToModel(User $user, $model): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if (!isset($user->level)) {
            return false;
        }

        return match ($user->level) {
            'CITY' => $this->sameCity($user, $model),
            'SUBCITY' => $this->sameSubcity($user, $model),
            'WEREDA' => $this->sameWereda($user, $model),
            default => false,
        };
    }
}