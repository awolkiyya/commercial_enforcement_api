<?php

namespace App\Queries;

use App\Models\Inspection;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use App\Services\Scopes\UniversalScopeEngine;

class InspectionQuery
{
    public function __construct(
        private Builder $query,
        private User $user,
        private UniversalScopeEngine $scopeEngine
    ) {}

    /**
     * Factory
     */
    public static function make(User $user): self
    {
        return new self(
            Inspection::query(),
            $user,
            app(UniversalScopeEngine::class)
        );
    }

    // =========================
    // APPLY PIPELINE
    // =========================
    public function apply(): self
    {
        $this->log('QUERY_START');

        $this->applyScope();
        $this->applyFilters();

        $this->log('QUERY_READY', [
            'sql' => $this->query->toSql(),
        ]);

        return $this;
    }

    /**
     * =========================
     * SCOPE APPLY
     * =========================
     */
    private function applyScope(): void
    {
        $this->log('SCOPE_APPLY_START', [
            'user_id' => $this->user->id,
            'role' => $this->user->role,
            'level' => $this->user->level,
        ]);

        $beforeSql = $this->query->toSql();

        $this->query = $this->scopeEngine->apply(
            $this->query,
            $this->user,
            [
                'owner_column' => 'inspector_id',
            ]
        );

        $this->log('SCOPE_APPLY_DONE', [
            'before_sql' => $beforeSql,
            'after_sql' => $this->query->toSql(),
        ]);
    }

    /**
     * =========================
     * FILTERS
     * =========================
     */
    private function applyFilters(): void
    {
        $this->log('FILTERS_START', request()->all());

        $this->filterSearch();
        $this->filterStatus();
        $this->filterTimeRange();

        $this->log('FILTERS_DONE');
    }

    private function filterSearch(): void
    {
        if ($search = request('search')) {

            $this->log('FILTER_SEARCH', ['search' => $search]);

            $this->query->where(function ($q) use ($search) {
                $q->where('inspection_number', 'ILIKE', "%{$search}%")
                  ->orWhereHas('business', function ($b) use ($search) {
                      $b->where('name', 'ILIKE', "%{$search}%");
                  });
            });
        }
    }

    private function filterStatus(): void
    {
        if ($status = request('status')) {

            $this->log('FILTER_STATUS', ['status' => $status]);

            $this->query->where('status', $status);
        }
    }

    private function filterTimeRange(): void
    {
        if (! $range = request('timeRange')) return;

        $this->log('FILTER_TIME_RANGE', ['range' => $range]);

        match ($range) {
            'today' => $this->query->whereDate('created_at', now()),

            'week' => $this->query->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]),

            'month' => $this->query->whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ]),

            'year' => $this->query->whereBetween('created_at', [
                now()->startOfYear(),
                now()->endOfYear(),
            ]),

            default => null,
        };
    }

    // =========================
    // RELATIONS
    // =========================
    public function withRelations(): self
    {
        $this->query->with([
            'business.businessType',
            'business.city',
            'business.subcity',
            'business.wereda',
            'business.owner',
            'violations.violationType',
            'penalty.penaltyType',
            'resolution.resolvedBy',
            'inspector',
        ]);

        return $this;
    }

    // =========================
    // PAGINATION + SUMMARY
    // =========================
    public function paginateWithSummary($request): array
    {
        $this->log('PAGINATION_START', [
            'per_page' => $request->get('per_page', 15),
        ]);

        $summary = (clone $this->query)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'ready_for_resolution' THEN 1 ELSE 0 END) as ready_for_resolution,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            ")
            ->first();

        $inspections = (clone $this->query)
            ->latest()
            ->paginate($request->get('per_page', 15));

        $this->log('PAGINATION_DONE', [
            'total' => $inspections->total(),
            'count' => $inspections->count(),
        ]);

        return [
            'data' => $inspections,

            'pagination' => [
                'current_page' => $inspections->currentPage(),
                'last_page' => $inspections->lastPage(),
                'per_page' => $inspections->perPage(),
                'total' => $inspections->total(),
            ],

            'summary' => [
                'total' => (int) ($summary->total ?? 0),
                'in_progress' => (int) ($summary->in_progress ?? 0),
                'ready_for_resolution' => (int) ($summary->ready_for_resolution ?? 0),
                'completed' => (int) ($summary->completed ?? 0),
            ],
        ];
    }

    // =========================
    // LOGGING
    // =========================
    private function log(string $event, array $context = []): void
    {
        try {
            Log::info('INSPECTION_QUERY', array_merge([
                'event' => $event,
                'user_id' => $this->user->id,
                'role' => $this->user->role,
                'level' => $this->user->level,
            ], $context));
        } catch (\Throwable $e) {
            // never break execution
        }
    }
}