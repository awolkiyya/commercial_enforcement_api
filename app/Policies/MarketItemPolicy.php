<?php

namespace App\Policies;

use App\Models\User;
use App\Models\MarketItem;
use App\Policies\Concerns\ChecksHierarchy;

class MarketItemPolicy
{
    use ChecksHierarchy;

    /*
    |--------------------------------------------------------------------------
    | LIST
    |--------------------------------------------------------------------------
    */

    public function viewAny(User $user): bool
    {
        return $this->isSystemAdmin($user)
            || $this->isAdmin($user);
    }

    /*
    |--------------------------------------------------------------------------
    | VIEW
    |--------------------------------------------------------------------------
    */

    public function view(
        User $user,
        MarketItem $item
    ): bool {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if (!$this->isAdmin($user)) {
            return false;
        }

        return $this->hasAccessToModel(
            $user,
            $item
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
            || $this->isAdmin($user);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        User $user,
        MarketItem $item
    ): bool {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if (!$this->isAdmin($user)) {
            return false;
        }

        return $this->hasAccessToModel(
            $user,
            $item
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function delete(
        User $user,
        MarketItem $item
    ): bool {
        return $this->isSystemAdmin($user);
    }
}