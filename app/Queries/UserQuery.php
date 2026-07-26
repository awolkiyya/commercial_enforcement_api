<?php

namespace App\Queries;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use App\Services\Scopes\UniversalScopeEngine;

class UserQuery
{
    public function __construct(
        private Builder $query,
        private User $authUser,
        private UniversalScopeEngine $scopeEngine
    ) {}

    /**
     * Factory
     */
    public static function make(User $authUser): self
    {
        return new self(
            User::query(),
            $authUser,
            app(UniversalScopeEngine::class)
        );
    }

    /**
     * =========================
     * PIPELINE
     * =========================
     */
    public function apply(array $filters = []): self
    {
        $this->log('QUERY_START', $filters);

        $this->applyScope();
        $this->applyFilters($filters);

        $this->log('QUERY_READY');

        return $this;
    }

    /**
     * =========================
     * SCOPE (SECURITY LAYER)
     * =========================
     */
    private function applyScope(): void
    {
        $this->log('SCOPE_START');

        $this->query = $this->scopeEngine->apply(
            $this->query,
            $this->authUser,
            [
                'owner_column' => null,
                'resource' => 'users',
            ]
        );

        /**
         * 🧠 EXTRA SAFETY RULE:
         * Never expose privileged accounts unless caller is allowed
         */
        $this->applyPrivilegeFiltering();

        $this->log('SCOPE_DONE');
    }

 /**
 * =========================
 * PRIVILEGE FILTERING (LEVEL-AWARE)
 * =========================
 */
private function applyPrivilegeFiltering(): void
{
    $callerRole = $this->authUser->role;
    $callerLevel = $this->authUser->level;

    /**
     * SUPER_ADMIN → no restriction
     */
    if ($callerRole === 'SUPER_ADMIN') {
        return;
    }

    /**
     * ADMIN → cannot see other ADMIN in SAME level
     */
    if ($callerRole === 'ADMIN') {

        $this->query->where(function ($q) use ($callerLevel) {

            $q->where('role', '!=', 'ADMIN')
              ->orWhere(function ($sub) use ($callerLevel) {
                  $sub->where('role', 'ADMIN')
                      ->where('level', '!=', $callerLevel);
              });
        });

        return;
    }

    /**
     * OTHER ROLES → block all privileged accounts
     */
    $this->query->whereNotIn('role', [
        'SUPER_ADMIN',
        'ADMIN',
    ]);
}

    /**
     * =========================
     * BUSINESS FILTERS
     * =========================
     */
    private function applyFilters(array $filters): void
    {
        $this->filterExcludeId($filters['exclude_id'] ?? null);
        $this->filterSearch($filters['search'] ?? null);
        $this->filterRole($filters['role'] ?? null);
        $this->filterLevel($filters['level'] ?? null);
        $this->filterActive($filters['is_active'] ?? null);
    }

    /**
     * EXCLUDE CURRENT USER
     */
    private function filterExcludeId(?string $id): void
    {
        if ($id) {
            $this->query->where('id', '!=', $id);
        }
    }

    /**
     * SEARCH
     */
    private function filterSearch(?string $search): void
    {
        if (!$search) return;

        $this->query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhere('phone', 'LIKE', "%{$search}%");
        });
    }

    /**
     * ROLE FILTER (SAFE OVERRIDE)
     */
    private function filterRole(?string $role): void
    {
        if (!$role) return;

        // Prevent bypassing privilege rules
        if (in_array($role, ['SUPER_ADMIN', 'ADMIN'])) {
            return;
        }

        $this->query->where('role', $role);
    }

    /**
     * LEVEL FILTER
     */
    private function filterLevel(?string $level): void
    {
        if ($level) {
            $this->query->where('level', $level);
        }
    }

    /**
     * ACTIVE STATUS
     */
    private function filterActive($isActive): void
    {
        if ($isActive === null) return;

        $this->query->where(
            'is_active',
            filter_var($isActive, FILTER_VALIDATE_BOOLEAN)
        );
    }

    /**
     * =========================
     * RELATIONS
     * =========================
     */
    public function withRelations(bool $enabled = true): self
    {
        if ($enabled) {
            $this->query->with([
                'roles',
                'permissions',
                'avatarFile',
                'city',
                'subcity',
                'wereda',
            ]);
        }

        return $this;
    }

    /**
     * =========================
     * PAGINATION
     * =========================
     */
    public function paginate(int $perPage = 15)
    {
        return $this->query
            ->latest()
            ->paginate($perPage);
    }

    /**
     * =========================
     * DEV LOGGING ONLY
     * =========================
     */
    private function log(string $event, array $context = []): void
    {
        if (!app()->isLocal()) return;

        try {
            Log::info('USER_QUERY', array_merge([
                'event' => $event,
                'auth_user_id' => $this->authUser->id,
                'role' => $this->authUser->role,
                'level' => $this->authUser->level,
            ], $context));
        } catch (\Throwable $e) {
            // never break query execution
        }
    }
}