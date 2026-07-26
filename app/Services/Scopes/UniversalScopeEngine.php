<?php

namespace App\Services\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class UniversalScopeEngine
{
    public function apply(Builder $query, User $user, array $options = []): Builder
    {
        $this->log('START_SCOPE', $user, $options);

        if ($this->isSuperAdmin($user)) {
            $this->log('SUPER_ADMIN_BYPASS', $user);
            return $query;
        }

        if (!$this->hasValidUserScope($user)) {
            return $this->deny($query, 'invalid_user_scope', $user);
        }

        return match ($user->role) {

            'ADMIN', 'SUPERVISOR' =>
                $this->applyGeographicScope($query, $user),

            'INSPECTOR' =>
                $this->applyInspectorScope($query, $user, $options),

            default =>
                $this->deny($query, 'unknown_role', $user),
        };
    }

    /**
     * =====================================================
     * GEOGRAPHIC SCOPE (FIXED: NO HARDCODED RELATIONS)
     * =====================================================
     */
    private function applyGeographicScope(Builder $query, User $user): Builder
    {
        $model   = $query->getModel();
        $table   = $model->getTable();
        $columns = Schema::getColumnListing($table);

        $strategy = ScopeStrategy::type($table, $columns);
        $column   = ScopeMap::column($user->level);

        $this->log('GEOGRAPHIC_SCOPE_START', $user, [
            'table' => $table,
            'strategy' => $strategy,
            'mapped_column' => $column,
        ]);

        /**
         * =========================
         * DIRECT
         * =========================
         */
        if ($strategy === 'direct') {

            if (!$column) {
                return $this->deny($query, 'missing_column_mapping', $user);
            }

            $value = $user->{$column} ?? null;

            if (!$value) {
                return $this->deny($query, 'missing_scope_value', $user);
            }

            return $query->where($column, $value);
        }

        /**
         * =========================
         * RELATION / MULTI-HOP (GENERIC FIX)
         * =========================
         */
        if (in_array($strategy, ['business_relation', 'inspection_business_relation'], true)) {

            $relationPath = $this->resolveRelationPath($strategy);

            $this->log('RELATION_SCOPE_USED', $user, [
                'relation_path' => implode('.', $relationPath),
            ]);

            return $query->whereHas($relationPath[0], function ($q) use ($relationPath, $user) {

                // build nested whereHas dynamically
                $this->applyNestedRelation($q, array_slice($relationPath, 1), $user);
            });
        }

        return $this->deny($query, 'unsupported_scope_strategy', $user);
    }

    /**
     * Resolve relation chain from strategy
     */
    private function resolveRelationPath(string $strategy): array
    {
        return match ($strategy) {
            'business_relation' => ['business'],
            'inspection_business_relation' => ['inspection', 'business'],
            default => []
        };
    }

    /**
     * Apply nested relations recursively
     */
    private function applyNestedRelation($query, array $relations, User $user): void
    {
        if (empty($relations)) {

            if ($user->city_id) {
                $query->where('city_id', $user->city_id);
            }

            if ($user->subcity_id) {
                $query->where('subcity_id', $user->subcity_id);
            }

            if ($user->wereda_id) {
                $query->where('wereda_id', $user->wereda_id);
            }

            return;
        }

        $relation = array_shift($relations);

        $query->whereHas($relation, function ($q) use ($relations, $user) {
            $this->applyNestedRelation($q, $relations, $user);
        });
    }

    /**
     * =====================================================
     * INSPECTOR SCOPE (UNCHANGED)
     * =====================================================
     */
    private function applyInspectorScope(Builder $query, User $user, array $options): Builder
{
    // If model supports direct column
    if (!empty($options['owner_column']) && Schema::hasColumn($query->getModel()->getTable(), $options['owner_column'])) {
        return $query->where($options['owner_column'], $user->id);
    }

    $table = $query->getModel()->getTable();

    // SPECIAL CASE: inspections table
    if ($table === 'inspections') {
        return $query->where('inspector_id', $user->id);
    }

    // DEFAULT: relation-based fallback
    return $query->whereHas('inspection', function ($q) use ($user) {
        $q->where('inspector_id', $user->id);
    });
}

    private function log(string $event, User $user, array $context = []): void
    {
        try {
            Log::info('SCOPE_ENGINE', [
                'event' => $event,
                'user_id' => $user->id ?? null,
                'role' => $user->role ?? null,
                'level' => $user->level ?? null,
                'context' => $context,
            ]);
        } catch (\Throwable) {}
    }

    private function hasValidUserScope(User $user): bool
    {
        return !empty($user->role) && !empty($user->level);
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->role === 'SUPER_ADMIN';
    }

    private function deny(Builder $query, string $reason = 'unknown', ?User $user = null): Builder
    {
        $this->log('DENY_SCOPE', $user ?? new User(), [
            'reason' => $reason,
        ]);

        return $query->whereRaw('1 = 0');
    }
}