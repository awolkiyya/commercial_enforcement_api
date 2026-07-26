<?php

namespace App\Services\Scopes;

final class ScopeMap
{
    /**
     * Maps user level to DB column
     */
    public static function column(string $level): ?string
    {
        return match ($level) {

            'CITY'    => 'city_id',
            'SUBCITY' => 'subcity_id',
            'WEREDA'  => 'wereda_id',

            default => null,
        };
    }
}