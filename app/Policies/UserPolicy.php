<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksHierarchy;

class UserPolicy
{
    use ChecksHierarchy;

    /**
     * VIEW USER LIST
     *
     * Only SUPER_ADMIN and ADMIN can access the user-management list.
     */
    public function viewAny(User $user): bool
    {
        return $this->isSystemAdmin($user)
            || $this->isAdmin($user);
    }

    /**
     * VIEW SINGLE USER
     *
     * SUPER_ADMIN:
     *     Can view every user.
     *
     * ADMIN:
     *     Can view users within the same city.
     *
     * SUPERVISOR:
     *     Can view users within the same subcity.
     *
     * INSPECTOR:
     *     Can view users within the same wereda.
     */
    public function view(User $user, User $model): bool
    {
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
        | ADMIN → SAME CITY
        |--------------------------------------------------------------------------
        */
        if (
            $this->isAdmin($user)
            && $this->sameCity($user, $model)
        ) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | SUPERVISOR → SAME SUBCITY
        |--------------------------------------------------------------------------
        */
        if (
            $this->isSupervisor($user)
            && $this->sameSubcity($user, $model)
        ) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | INSPECTOR → SAME WEREDA
        |--------------------------------------------------------------------------
        */
        if (
            $this->isInspector($user)
            && $this->sameWereda($user, $model)
        ) {
            return true;
        }

        return false;
    }

    /**
     * CREATE USER
     *
     * Determines whether the authenticated user is allowed to
     * access the user-creation operation.
     *
     * IMPORTANT:
     * This does NOT determine which role may be assigned.
     *
     * Role assignment is separately enforced by assignRole().
     */
    public function create(User $user): bool
    {
        return $this->isSystemAdmin($user)
            || $this->isAdmin($user);
    }

    /**
     * ASSIGN ROLE TO A NEW USER
     *
     * This is the critical authorization check for the
     * vertical privilege-escalation vulnerability.
     *
     * Allowed hierarchy:
     *
     * SUPER_ADMIN → ADMIN
     * SUPER_ADMIN → SUPERVISOR
     * SUPER_ADMIN → INSPECTOR
     *
     * ADMIN → SUPERVISOR
     * ADMIN → INSPECTOR
     *
     * ADMIN → SUPER_ADMIN        DENIED
     *
     * SUPERVISOR → ANY ROLE      DENIED
     *
     * INSPECTOR → ANY ROLE       DENIED
     */
    public function assignRole(
        User $user,
        string $targetRole
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | Must have the base role-assignment permission
        |--------------------------------------------------------------------------
        */
        if (!$user->can('users.assign_role')) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Enforce role hierarchy
        |--------------------------------------------------------------------------
        */
        return $this->canAssignRole($user, $targetRole);
    }

    /**
     * UPDATE USER
     *
     * Controls whether the authenticated user can update
     * the target user's normal account information.
     *
     * Role changes must additionally pass updateRole().
     */
    public function update(User $user, User $model): bool
    {
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
        | Prevent ADMIN from modifying SUPER_ADMIN accounts
        |--------------------------------------------------------------------------
        */
        if (
            $model->hasRole('SUPER_ADMIN')
            && !$this->isSystemAdmin($user)
        ) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | ADMIN → SAME CITY
        |--------------------------------------------------------------------------
        */
        if (
            $this->isAdmin($user)
            && $this->sameCity($user, $model)
        ) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | SUPERVISOR → SAME SUBCITY
        |--------------------------------------------------------------------------
        */
        if (
            $this->isSupervisor($user)
            && $this->sameSubcity($user, $model)
        ) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | INSPECTOR → SAME WEREDA
        |--------------------------------------------------------------------------
        */
        if (
            $this->isInspector($user)
            && $this->sameWereda($user, $model)
        ) {
            return true;
        }

        return false;
    }

    /**
     * DELETE USER
     *
     * Only SUPER_ADMIN may delete users.
     *
     * This prevents lower-level administrators from deleting
     * privileged or operational accounts.
     */
    public function delete(User $user, User $model): bool
    {
        if (!$this->isSystemAdmin($user)) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent deleting the currently authenticated account
        |--------------------------------------------------------------------------
        */
        if ($user->id === $model->id) {
            return false;
        }

        return true;
    }

    /**
     * UPDATE PASSWORD
     *
     * SUPER_ADMIN can update any user's password.
     *
     * Any user may update their own password.
     */
    public function updatePassword(User $user, User $model): bool
    {
        return $this->isSystemAdmin($user)
            || $user->id === $model->id;
    }

    /**
     * UPDATE USER STATUS
     *
     * SUPER_ADMIN:
     *     Can change any user's status.
     *
     * ADMIN:
     *     Can change status only for users in the same city.
     *
     * IMPORTANT:
     * ADMIN cannot deactivate SUPER_ADMIN accounts.
     */
    public function updateStatus(User $user, User $model): bool
    {
        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |--------------------------------------------------------------------------
        */
        if ($this->isSystemAdmin($user)) {
            return $user->id !== $model->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Never allow ADMIN to modify SUPER_ADMIN status
        |--------------------------------------------------------------------------
        */
        if ($model->hasRole('SUPER_ADMIN')) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | ADMIN → SAME CITY
        |--------------------------------------------------------------------------
        */
        if (
            $this->isAdmin($user)
            && $this->sameCity($user, $model)
        ) {
            return true;
        }

        return false;
    }

    /**
     * UPDATE ROLE
     *
     * Controls changing the role of an EXISTING user.
     *
     * The same hierarchy used during creation is enforced here.
     *
     * Examples:
     *
     * ADMIN → SUPERVISOR       ALLOWED
     * ADMIN → INSPECTOR        ALLOWED
     * ADMIN → ADMIN            ALLOWED/NO-OP depending on service
     * ADMIN → SUPER_ADMIN      DENIED
     *
     * SUPER_ADMIN → any normal role
     * SUPER_ADMIN → SUPER_ADMIN is allowed only if your service
     * handles it safely; otherwise it can be treated as no-op.
     */
    public function updateRole(
        User $user,
        User $model,
        string $targetRole
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | Must have role-assignment permission
        |--------------------------------------------------------------------------
        */
        if (!$user->can('users.assign_role')) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Never allow a user to change their own role
        |--------------------------------------------------------------------------
        */
        if ($user->id === $model->id) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Lower-level users cannot modify SUPER_ADMIN
        |--------------------------------------------------------------------------
        */
        if (
            $model->hasRole('SUPER_ADMIN')
            && !$this->isSystemAdmin($user)
        ) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Enforce target-role hierarchy
        |--------------------------------------------------------------------------
        */
        return $this->canAssignRole($user, $targetRole);
    }
}