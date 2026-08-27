<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Inspection;
use App\Models\InspectionClosureRequest;
use App\Policies\Concerns\ChecksHierarchy;

class InspectionClosureRequestPolicy
{
    use ChecksHierarchy;

    /*
    |--------------------------------------------------------------------------
    | LIST
    |--------------------------------------------------------------------------
    |
    | Inspectors are allowed to enter the closure-request list because
    | they need to see requests they have submitted.
    |
    | IMPORTANT:
    | This ONLY authorizes access to the endpoint.
    |
    | The actual query MUST still be scoped in ClosureRequestQuery.
    |
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
    | CREATE CLOSURE REQUEST
    |--------------------------------------------------------------------------
    */

    public function createClosureRequest(
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
        | ADMIN / SUPERVISOR / INSPECTOR
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isAdmin($user)
            && !$this->isSupervisor($user)
            && !$this->isInspector($user)
        ) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | OBJECT-LEVEL GEOGRAPHIC AUTHORIZATION
        |--------------------------------------------------------------------------
        |
        | This prevents a user from creating a closure request against
        | an inspection outside their authorized administrative scope.
        |
        |--------------------------------------------------------------------------
        */

        return $this->hasAccessToModel(
            $user,
            $inspection
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VIEW SINGLE CLOSURE REQUEST
    |--------------------------------------------------------------------------
    |
    | This protects:
    |
    | GET /closure-requests/{id}
    |
    | It is important because filtering the list is NOT enough to
    | prevent BOLA/IDOR.
    |--------------------------------------------------------------------------
    */

    public function view(
        User $user,
        InspectionClosureRequest $closureRequest
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
        | ADMIN / SUPERVISOR
        |--------------------------------------------------------------------------
        */

        if (
            $this->isAdmin($user)
            || $this->isSupervisor($user)
        ) {
            return $this->hasAccessToModel(
                $user,
                $closureRequest
            );
        }

        /*
        |--------------------------------------------------------------------------
        | INSPECTOR
        |--------------------------------------------------------------------------
        |
        | Inspector can only view their own submitted request.
        |
        |--------------------------------------------------------------------------
        */

        if ($this->isInspector($user)) {

            return (string) $closureRequest->user_id
                === (string) $user->id;
        }

        /*
        |--------------------------------------------------------------------------
        | FAIL CLOSED
        |--------------------------------------------------------------------------
        */

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | DECIDE
    |--------------------------------------------------------------------------
    |
    | Only administrative/review roles can approve/reject.
    |
    |--------------------------------------------------------------------------
    */

    public function decide(
        User $user,
        InspectionClosureRequest $closureRequest
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
        | ADMIN / SUPERVISOR ONLY
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isAdmin($user)
            && !$this->isSupervisor($user)
        ) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | OBJECT-LEVEL GEOGRAPHIC AUTHORIZATION
        |--------------------------------------------------------------------------
        |
        | Prefer checking the associated inspection because the inspection
        | is the actual geographic object being processed.
        |
        |--------------------------------------------------------------------------
        */

        $inspection = $closureRequest->inspection;

        if (!$inspection) {
            return false;
        }

        return $this->hasAccessToModel(
            $user,
            $inspection
        );
    }
}