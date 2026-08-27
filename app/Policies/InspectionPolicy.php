<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Inspection;
use App\Policies\Concerns\ChecksHierarchy;

class InspectionPolicy
{
    use ChecksHierarchy;

    /*
    |--------------------------------------------------------------------------
    | LIST INSPECTIONS
    |--------------------------------------------------------------------------
    */

    public function viewAny(User $user): bool
    {
        return $this->isSystemAdmin($user)
            || $this->isAdmin($user)
            || $this->isSupervisor($user)
            || $this->isInspector($user);
    }

    /*
    |--------------------------------------------------------------------------
    | VIEW SINGLE INSPECTION
    |--------------------------------------------------------------------------
    |
    | CRITICAL BOLA/IDOR PROTECTION
    |--------------------------------------------------------------------------
    */

    public function view(
        User $user,
        Inspection $inspection
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN → GLOBAL
        |--------------------------------------------------------------------------
        */

        if ($this->isSystemAdmin($user)) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | ADMIN / SUPERVISOR / INSPECTOR
        |--------------------------------------------------------------------------
        |
        | All must remain inside their geographic scope.
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isAdmin($user)
            && !$this->isSupervisor($user)
            && !$this->isInspector($user)
        ) {
            return false;
        }

        return $this->hasAccessToModel(
            $user,
            $inspection
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create(User $user): bool
    {
        return $this->isSystemAdmin($user)
            || $this->isAdmin($user)
            || $this->isSupervisor($user)
            || $this->isInspector($user);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        User $user,
        Inspection $inspection
    ): bool {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if (
            !$this->isAdmin($user)
            && !$this->isSupervisor($user)
            && !$this->isInspector($user)
        ) {
            return false;
        }

        return $this->hasAccessToModel(
            $user,
            $inspection
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ESCALATE PENALTY
    |--------------------------------------------------------------------------
    |
    | Escalation is more privileged than ordinary inspection access.
    |--------------------------------------------------------------------------
    */

    public function escalate(
        User $user,
        Inspection $inspection
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
        | ONLY ADMIN
        |--------------------------------------------------------------------------
        |
        | Ordinary inspectors and supervisors cannot escalate.
        |--------------------------------------------------------------------------
        */

        if (!$this->isAdmin($user)) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | ADMIN MUST BE IN AUTHORIZED SCOPE
        |--------------------------------------------------------------------------
        */

        return $this->hasAccessToModel(
            $user,
            $inspection
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function delete(
        User $user,
        Inspection $inspection
    ): bool {
        return $this->isSystemAdmin($user);
    }
}