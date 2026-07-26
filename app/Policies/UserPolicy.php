<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksHierarchy;

class UserPolicy
{
    use ChecksHierarchy;

    /**
     * VIEW LIST
     */
    public function viewAny(User $user): bool
    {
        return $this->isSystemAdmin($user)
            || $this->isAdmin($user);
    }

    /**
     * VIEW SINGLE USER
     */
    public function view(User $user, User $model): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        // ADMIN can see users in same city
        if ($this->isAdmin($user) && $this->sameCity($user, $model)) {
            return true;
        }

        // SUPERVISOR can see subcity users
        if ($this->isSupervisor($user) && $this->sameSubcity($user, $model)) {
            return true;
        }

        // INSPECTOR can see wereda-level users
        if ($this->isInspector($user) && $this->sameWereda($user, $model)) {
            return true;
        }

        return false;
    }

    /**
     * CREATE USER
     */
    public function create(User $user): bool
    {
        return $this->isSystemAdmin($user)
            || $this->isAdmin($user);
    }

    /**
     * UPDATE USER
     */
    public function update(User $user, User $model): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($this->isAdmin($user) && $this->sameCity($user, $model)) {
            return true;
        }

        if ($this->isSupervisor($user) && $this->sameSubcity($user, $model)) {
            return true;
        }

        if ($this->isInspector($user) && $this->sameWereda($user, $model)) {
            return true;
        }

        return false;
    }

    /**
     * DELETE USER (STRICT)
     */
    public function delete(User $user, User $model): bool
    {
        return $this->isSystemAdmin($user);
    }

    /**
     * UPDATE PASSWORD
     */
    public function updatePassword(User $user, User $model): bool
    {
        return $this->isSystemAdmin($user)
            || $user->id === $model->id;
    }

    /**
     * UPDATE STATUS
     */
    public function updateStatus(User $user, User $model): bool
    {
        return $this->isSystemAdmin($user)
            || $this->isAdmin($user);
    }

    /**
     * UPDATE ROLE (VERY STRICT)
     */
    public function updateRole(User $user): bool
    {
        return $this->isSystemAdmin($user);
    }
}