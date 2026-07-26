<?php

namespace App\Services\Scopes;

class ScopeStrategy
{
    public static function type(string $table, array $columns = []): string
    {
        $table = strtolower($table);

        return match ($table) {

            /**
             * DIRECT SCOPED TABLES
             */
            'users',
            'businesses' => 'direct',

            /**
             * INSPECTIONS → business relation
             */
            'inspections' => self::resolveInspectionStrategy($columns),

            /**
             * RESOLUTIONS → inspection → business
             */
            'resolutions' => self::resolveResolutionStrategy($columns),

            /**
             * INSPECTION CLOSURE REQUESTS → inspection → business (IMPORTANT FIX)
             */
            'inspection_closure_requests' => self::resolveInspectionClosureRequestStrategy($columns),

            default => 'direct',
        };
    }

    /**
     * =========================
     * INSPECTIONS
     * =========================
     */
    private static function resolveInspectionStrategy(array $columns): string
    {
        if (in_array('business_id', $columns, true)) {
            return 'business_relation';
        }

        return 'direct';
    }

    /**
     * =========================
     * RESOLUTIONS
     * =========================
     */
    private static function resolveResolutionStrategy(array $columns): string
    {
        return in_array('inspection_id', $columns, true)
            ? 'inspection_business_relation'
            : 'direct';
    }

    /**
     * =========================
     * INSPECTION CLOSURE REQUESTS (FIXED + IMPORTANT)
     * =========================
     */
    private static function resolveInspectionClosureRequestStrategy(array $columns): string
    {
        /**
         * PRIMARY PATH:
         * inspection_closure_requests → inspection → business
         */
        if (in_array('inspection_id', $columns, true)) {
            return 'inspection_business_relation';
        }

        return 'direct';
    }
}