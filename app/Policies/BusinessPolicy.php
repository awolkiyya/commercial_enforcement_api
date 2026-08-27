<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Business;
use App\Policies\Concerns\ChecksHierarchy;

class BusinessPolicy
{
    use ChecksHierarchy;

    /*
    |--------------------------------------------------------------------------
    | VIEW ANY
    |--------------------------------------------------------------------------
    */

    public function viewAny(User $user): bool
    {
        /*
        |--------------------------------------------------------------------------
        | Explicit role check.
        |
        | Do NOT return true.
        |--------------------------------------------------------------------------
        */

        return $user->hasAnyRole([
            'SUPER_ADMIN',
            'ADMIN',
            'SUPERVISOR',
            'INSPECTOR',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VIEW
    |--------------------------------------------------------------------------
    */

    public function view(
        User $user,
        Business $business
    ): bool {
        return $this->hasAccessToModel(
            $user,
            $business
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create(User $user): bool
    {
        /*
        |--------------------------------------------------------------------------
        | Adjust this according to your actual business workflow.
        |
        | For example, if only administrative users create businesses:
        |--------------------------------------------------------------------------
        */

        return $user->hasAnyRole([
            'SUPER_ADMIN',
            'ADMIN',
            'SUPERVISOR',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        User $user,
        Business $business
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |--------------------------------------------------------------------------
        */

        if ($this->isSystemAdmin($user)) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | User must have appropriate role
        |--------------------------------------------------------------------------
        */

        if (!$user->hasAnyRole([
            'ADMIN',
            'SUPERVISOR',
        ])) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Geographic scope
        |--------------------------------------------------------------------------
        */

        return $this->hasAccessToModel(
            $user,
            $business
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CHANGE STATUS
    |--------------------------------------------------------------------------
    */

    public function changeStatus(
        User $user,
        Business $business
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |--------------------------------------------------------------------------
        */

        if ($this->isSystemAdmin($user)) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Only administrative roles
        |--------------------------------------------------------------------------
        */

        if (!$user->hasAnyRole([
            'ADMIN',
            'SUPERVISOR',
        ])) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Must be within user's geographic scope
        |--------------------------------------------------------------------------
        */

        return $this->hasAccessToModel(
            $user,
            $business
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function delete(
        User $user,
        Business $business
    ): bool {
        return $this->isSystemAdmin($user);
    }
}