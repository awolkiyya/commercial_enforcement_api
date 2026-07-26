<?php

namespace App\Queries;

use App\Models\Resolution;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use App\Services\Scopes\UniversalScopeEngine;

class ResolutionQuery
{
    private Builder $query;
    private bool $scoped = false;

    public function __construct(
        Builder $query,
        private User $user,
        private UniversalScopeEngine $scopeEngine
    ) {
        $this->query = $query;
    }

    /**
     * =========================
     * FACTORY
     * =========================
     */
    public static function make(User $user): self
    {
        return new self(
            Resolution::query(),
            $user,
            app(UniversalScopeEngine::class)
        );
    }

    /**
     * =========================
     * PIPELINE ENTRY
     * =========================
     */
    public function apply(array $filters = []): self
    {
        $this->log('QUERY_START');

        $this->applyScope();
        $this->applyFilters($filters);
        $this->applyRelations();

        $this->log('QUERY_READY', [
            'sql' => $this->query->toSql(),
        ]);

        return $this;
    }

    /**
     * =========================
     * SCOPE (FAIL-FAST SAFE)
     * =========================
     */
    private function applyScope(): void
    {
        if ($this->scoped) {
            throw new \RuntimeException('Scope already applied');
        }

        $this->scoped = true;

        $this->log('SCOPE_APPLY_START', [
            'user_id' => $this->user->id,
            'role' => $this->user->role,
        ]);

        $beforeSql = $this->query->toSql();

        $this->query = $this->scopeEngine->apply(
            $this->query,
            $this->user,
            [
                'relation' => 'inspection.business',
            ]
        );

        $this->log('SCOPE_APPLY_DONE', [
            'before_sql' => $beforeSql,
            'after_sql' => $this->query->toSql(),
        ]);
    }

    /**
     * =========================
     * FILTERS (PURE INPUT)
     * =========================
     */
    private function applyFilters(array $filters): void
    {
        $this->log('FILTERS_START', $filters);

        $this->filterOutcome($filters);
        $this->filterInspection($filters);
        $this->filterBusiness($filters);
        $this->filterDateRange($filters);

        $this->log('FILTERS_DONE');
    }

    private function filterOutcome(array $filters): void
    {
        if (!empty($filters['outcome'])) {
            $this->log('FILTER_OUTCOME', $filters);
            $this->query->where('outcome', $filters['outcome']);
        }
    }

    private function filterInspection(array $filters): void
    {
        if (!empty($filters['inspection_id'])) {
            $this->log('FILTER_INSPECTION', $filters);
            $this->query->where('inspection_id', $filters['inspection_id']);
        }
    }

    private function filterBusiness(array $filters): void
    {
        if (!empty($filters['business_id'])) {
            $this->log('FILTER_BUSINESS', $filters);

            $this->query->whereHas('inspection', function ($q) use ($filters) {
                $q->where('business_id', $filters['business_id']);
            });
        }
    }

    private function filterDateRange(array $filters): void
    {
        if (!empty($filters['from_date'])) {
            $this->log('FILTER_FROM_DATE', $filters);
            $this->query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $this->log('FILTER_TO_DATE', $filters);
            $this->query->whereDate('created_at', '<=', $filters['to_date']);
        }
    }

    /**
     * =========================
     * RELATIONS
     * =========================
     */
    private function applyRelations(): void
    {
        $this->query->with([
            'inspection.business',
            'resolvedBy',
        ]);
    }

    /**
     * =========================
     * PAGINATION (FINAL STEP ONLY)
     * =========================
     */
    public function paginate($request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $this->log('PAGINATION_START', [
            'per_page' => $perPage,
        ]);

        $result = $this->query
            ->latest()
            ->paginate($perPage);

        $this->log('PAGINATION_DONE', [
            'total' => $result->total(),
            'count' => $result->count(),
        ]);

        return $result;
    }

    /**
     * =========================
     * LOGGING (SAFE)
     * =========================
     */
    private function log(string $event, array $context = []): void
    {
        try {
            Log::info('RESOLUTION_QUERY', [
                'event' => $event,
                'user_id' => $this->user->id,
                'role' => $this->user->role ?? null,
                'context' => $context,
            ]);
        } catch (\Throwable) {
            // never break execution
        }
    }
}