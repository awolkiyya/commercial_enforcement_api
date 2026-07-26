<?php

namespace App\Queries;

use App\Models\InspectionClosureRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use App\Services\Scopes\UniversalScopeEngine;

class ClosureRequestQuery
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
            InspectionClosureRequest::query(),
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

        $this->filterStatus($filters);
        $this->filterSort($filters);
        $this->filterDateRange($filters);

        $this->log('FILTERS_DONE');
    }

    private function filterStatus(array $filters): void
    {
        if (!empty($filters['status'])) {
            $this->query->where('status', $filters['status']);
        }
    }

    private function filterSort(array $filters): void
    {
        $sort = $filters['sort'] ?? null;

        if (in_array($sort, ['asc', 'desc'], true)) {
            $this->query->orderBy('created_at', $sort);
        } else {
            $this->query->orderByDesc('created_at');
        }
    }

    private function filterDateRange(array $filters): void
    {
        if (!empty($filters['from_date'])) {
            $this->query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
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
            'requestedBy',
            'reviewedBy',
        ]);
    }

    /**
     * =========================
     * SUMMARY (IMPORTANT FOR UI)
     * =========================
     */
    public function summary(): array
    {
        $base = clone $this->query;

        return [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'approved' => (clone $base)->where('status', 'approved')->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
        ];
    }

    /**
     * =========================
     * PAGINATION (FINAL STEP ONLY)
     * =========================
     */
    public function paginate($request)
    {
        $perPage = (int) $request->get('per_page', 10);

        $this->log('PAGINATION_START', [
            'per_page' => $perPage,
        ]);

        $result = $this->query
            ->paginate($perPage);

        $this->log('PAGINATION_DONE', [
            'total' => $result->total(),
            'count' => $result->count(),
        ]);

        return $result;
    }

    /**
     * =========================
     * RAW QUERY ACCESS (SAFE)
     * =========================
     */
    public function baseQuery(): Builder
    {
        return $this->query;
    }

    /**
     * =========================
     * LOGGING (SAFE)
     * =========================
     */
    private function log(string $event, array $context = []): void
    {
        try {
            Log::info('CLOSURE_REQUEST_QUERY', [
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