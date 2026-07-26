<?php

namespace App\Contracts\Scopes;

use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

interface DataScopeResolver
{
    public function apply(Builder $query, User $user): Builder;
}