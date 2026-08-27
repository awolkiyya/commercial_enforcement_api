<?php

namespace App\Policies;

use App\Models\User;
use App\Models\MarketCategory;
use App\Policies\Concerns\ChecksHierarchy;

class MarketCategoryPolicy
{
    use ChecksHierarchy;

    /**
     * LIST
     */
    public function viewAny(User $user): bool
    {
        return $this->isSystemAdmin($user)
            || $this->isAdmin($user);
    }

    /**
     * VIEW
     */
    public function view(
        User $user,
        MarketCategory $category
    ): bool {
        return $this->isSystemAdmin($user)
            || (
                $this->isAdmin($user)
                && $this->hasAccessToModel(
                    $user,
                    $category
                )
            );
    }

    /**
     * CREATE
     */
    public function create(User $user): bool
    {
        return $this->isSystemAdmin($user)
            || $this->isAdmin($user);
    }

    /**
     * UPDATE
     */
    public function update(
        User $user,
        MarketCategory $category
    ): bool {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $this->isAdmin($user)
            && $this->hasAccessToModel(
                $user,
                $category
            );
    }

    /**
     * DELETE
     */
    public function delete(
        User $user,
        MarketCategory $category
    ): bool {
        return $this->isSystemAdmin($user);
    }
}