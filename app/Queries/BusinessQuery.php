<?php

namespace App\Queries;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use App\Services\Scopes\UniversalScopeEngine;

class BusinessQuery
{
    public function __construct(
        private Builder $query,
        private User $user,
        private UniversalScopeEngine $scopeEngine
    ) {}

    /**
     * =========================
     * FACTORY
     * =========================
     */
    public static function make(User $user): self
    {
        return new self(
            Business::query(),
            $user,
            app(UniversalScopeEngine::class)
        );
    }

    /**
     * =========================
     * APPLY PIPELINE
     * =========================
     */
    public function apply(): self
    {
        $this->log('QUERY_START');

        $this->applyScope();
        $this->applyFilters();
        $this->applyRelations();

        $this->log('QUERY_READY', [
            'sql' => $this->query->toSql(),
        ]);

        return $this;
    }

    /**
     * =========================
     * SCOPE ENGINE
     * =========================
     */
    private function applyScope(): void
    {
        $this->log('SCOPE_APPLY_START', [
            'user_id' => $this->user->id,
            'role' => $this->user->role,
        ]);

        $beforeSql = $this->query->toSql();

        $this->query = $this->scopeEngine->apply(
            $this->query,
            $this->user,
            [
                'owner_column' => 'owner_id', // adjust if needed
            ]
        );

        $this->log('SCOPE_APPLY_DONE', [
            'before_sql' => $beforeSql,
            'after_sql' => $this->query->toSql(),
        ]);
    }

    /**
     * =========================
     * FILTER PIPELINE
     * =========================
     */
    private function applyFilters(): void
    {
        $this->log('FILTERS_START', request()->all());

        $this->filterSearch();
        $this->filterBusinessType();
        $this->filterWereda();
        $this->filterStatus();
        $this->filterRegisteredBy();

        $this->log('FILTERS_DONE');
    }

    /**
     * =========================
     * SEARCH FILTER
     * =========================
     */
    private function filterSearch(): void
    {
        if (! $search = request('search')) return;

        $this->log('FILTER_SEARCH', ['search' => $search]);

        $term = strtolower($search);

        $this->query->where(function ($q) use ($term) {

            // BUSINESS CORE
            $q->whereRaw('LOWER(name) ILIKE ?', ["%{$term}%"])
              ->orWhereRaw('LOWER(trade_name) ILIKE ?', ["%{$term}%"])
              ->orWhereRaw('LOWER(license_number) ILIKE ?', ["%{$term}%"])
              ->orWhereRaw('LOWER(tin_number) ILIKE ?', ["%{$term}%"])

              // OWNER
              ->orWhereHas('owner', function ($owner) use ($term) {
                  $owner->whereRaw('LOWER(full_name) ILIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(phone) ILIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(email) ILIKE ?', ["%{$term}%"]);
              })

              // REGISTERED BY
              ->orWhereHas('registeredBy', function ($user) use ($term) {
                  $user->whereRaw('LOWER(name) ILIKE ?', ["%{$term}%"])
                       ->orWhereRaw('LOWER(email) ILIKE ?', ["%{$term}%"]);
              })

              // WEREDA
              ->orWhereHas('wereda', function ($wereda) use ($term) {
                  $wereda->whereRaw('LOWER(name) ILIKE ?', ["%{$term}%"]);
              });
        });
    }

    /**
     * =========================
     * BUSINESS TYPE FILTER
     * =========================
     */
    private function filterBusinessType(): void
    {
        if ($id = request('business_type_id')) {

            $this->log('FILTER_BUSINESS_TYPE', ['id' => $id]);

            $this->query->where('business_type_id', $id);
        }
    }

    /**
     * =========================
     * WEREDA FILTER
     * =========================
     */
    private function filterWereda(): void
    {
        if ($id = request('wereda_id')) {

            $this->log('FILTER_WEREDA', ['id' => $id]);

            $this->query->where('wereda_id', $id);
        }
    }

    /**
     * =========================
     * STATUS FILTER
     * =========================
     */
    private function filterStatus(): void
    {
        if ($status = request('status')) {

            $this->log('FILTER_STATUS', ['status' => $status]);

            $this->query->where('status', $status);
        }
    }

    /**
     * =========================
     * REGISTERED BY FILTER
     * =========================
     */
    private function filterRegisteredBy(): void
    {
        if ($id = request('registered_by')) {

            $this->log('FILTER_REGISTERED_BY', ['id' => $id]);

            $this->query->where('registered_by', $id);
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
            'owner',
            'businessType',
            'city',
            'subcity',
            'wereda',
            'registeredBy',
        ]);
    }

    /**
     * =========================
     * GET RESULTS (PAGINATION)
     * =========================
     */
    public function paginate($request)
    {
        $this->log('PAGINATION_START', [
            'per_page' => $request->get('per_page', 15),
        ]);

        $data = (clone $this->query)
            ->latest()
            ->paginate($request->get('per_page', 15));

        $this->log('PAGINATION_DONE', [
            'total' => $data->total(),
        ]);

        return $data;
    }

    /**
     * =========================
     * LOGGING
     * =========================
     */
    private function log(string $event, array $context = []): void
    {
        try {
            Log::info('BUSINESS_QUERY', array_merge([
                'event' => $event,
                'user_id' => $this->user->id,
                'role' => $this->user->role ?? null,
            ], $context));
        } catch (\Throwable $e) {
            // prevent query failure
        }
    }
}