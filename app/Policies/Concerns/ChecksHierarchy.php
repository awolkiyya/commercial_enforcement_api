<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

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
    | ROLE ASSIGNMENT HIERARCHY
    |--------------------------------------------------------------------------
    |
    | This determines WHICH roles an authenticated user may assign.
    |
    | IMPORTANT:
    |
    | Spatie permission:
    |     users.assign_role
    |
    | determines whether the user has the general ability to assign
    | roles.
    |
    | This method determines which specific target role is permitted.
    |
    | Hierarchy:
    |
    | SUPER_ADMIN
    |     -> ADMIN
    |     -> SUPERVISOR
    |     -> INSPECTOR
    |
    | ADMIN
    |     -> SUPERVISOR
    |     -> INSPECTOR
    |
    | SUPERVISOR
    |     -> NONE
    |
    | INSPECTOR
    |     -> NONE
    |
    | SUPER_ADMIN can NEVER be assigned through normal user-management
    | operations.
    |
    |--------------------------------------------------------------------------
    */

    protected function canAssignRole(
        User $user,
        string $targetRole
    ): bool {
        $targetRole = strtoupper(trim($targetRole));

        /*
        |--------------------------------------------------------------------------
        | NEVER allow SUPER_ADMIN to be assigned through this hierarchy
        |--------------------------------------------------------------------------
        |
        | This is an explicit deny rule.
        |
        | Even if someone accidentally adds SUPER_ADMIN to another role's
        | allowed-role list later, this guard still blocks it.
        |
        |--------------------------------------------------------------------------
        */

        if ($targetRole === 'SUPER_ADMIN') {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |--------------------------------------------------------------------------
        |
        | SUPER_ADMIN may assign all lower-level application roles.
        |
        |--------------------------------------------------------------------------
        */

        if ($this->isSystemAdmin($user)) {
            return in_array($targetRole, [
                'ADMIN',
                'SUPERVISOR',
                'INSPECTOR',
            ], true);
        }

        /*
        |--------------------------------------------------------------------------
        | ADMIN
        |--------------------------------------------------------------------------
        |
        | ADMIN may assign operational roles below ADMIN.
        |
        |--------------------------------------------------------------------------
        */

        if ($this->isAdmin($user)) {
            return in_array($targetRole, [
                'SUPERVISOR',
                'INSPECTOR',
            ], true);
        }

        /*
        |--------------------------------------------------------------------------
        | SUPERVISOR
        |--------------------------------------------------------------------------
        */

        if ($this->isSupervisor($user)) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | INSPECTOR
        |--------------------------------------------------------------------------
        */

        if ($this->isInspector($user)) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | UNKNOWN ROLE
        |--------------------------------------------------------------------------
        |
        | Fail closed.
        |
        |--------------------------------------------------------------------------
        */

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | GOVERNANCE SCOPE MATCHING
    |--------------------------------------------------------------------------
    |
    | UUID-safe comparisons.
    |
    |--------------------------------------------------------------------------
    */

    protected function sameCity(
        User $user,
        Model $model
    ): bool {
        return !empty($user->city_id)
            && !empty($model->city_id)
            && (string) $user->city_id === (string) $model->city_id;
    }

    protected function sameSubCity(
        User $user,
        Model $model
    ): bool {
        return !empty($user->subcity_id)
            && !empty($model->subcity_id)
            && (string) $user->subcity_id === (string) $model->subcity_id;
    }

    protected function sameWereda(
        User $user,
        Model $model
    ): bool {
        return !empty($user->wereda_id)
            && !empty($model->wereda_id)
            && (string) $user->wereda_id === (string) $model->wereda_id;
    }

    /*
    |--------------------------------------------------------------------------
    | GOVERNANCE ACCESS
    |--------------------------------------------------------------------------
    |
    | Determines whether the authenticated user can access a model
    | within their administrative geographic scope.
    |
    |--------------------------------------------------------------------------
    */

    protected function hasAccessToModel(
        User $user,
        Model $model
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN → GLOBAL ACCESS
        |--------------------------------------------------------------------------
        */

        if ($this->isSystemAdmin($user)) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Missing level → DENY
        |--------------------------------------------------------------------------
        */

        if (empty($user->level)) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Scope-based authorization
        |--------------------------------------------------------------------------
        */

        return match (strtoupper((string) $user->level)) {

            'CITY' => $this->sameCity(
                $user,
                $model
            ),

            'SUBCITY' => $this->sameSubCity(
                $user,
                $model
            ),

            'WEREDA' => $this->sameWereda(
                $user,
                $model
            ),

            default => false,
        };
    }
}