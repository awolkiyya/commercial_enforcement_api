<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Users\Requests\StoreUserRequest;
use App\Modules\Users\Requests\UpdatePasswordRequest;
use App\Modules\Users\Requests\UpdateUserRequest;
use App\Modules\Users\Resources\UserResource;
use App\Modules\Users\Services\UserService;
use App\Queries\UserQuery;
use App\Support\ApiResponse;
use App\Support\PaginatesResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Throwable;

class UserController extends Controller
{
    use AuthorizesRequests, PaginatesResponse;

    public function __construct(
        protected UserService $userService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | LOGGING HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Return safe request information.
     *
     * IMPORTANT:
     * Never log passwords, tokens, cookies, authorization headers,
     * or other secrets.
     */
    private function requestContext(?Request $request = null): array
    {
        $request ??= request();

        $input = $request->except([
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'new_password_confirmation',
            'token',
            'access_token',
            'refresh_token',
        ]);

        return [
            'request_id' => $request->header('X-Request-ID')
                ?? $request->header('X-Correlation-ID')
                ?? null,

            'method' => $request->method(),

            'url' => $request->fullUrl(),

            'path' => $request->path(),

            'ip' => $request->ip(),

            'user_agent' => $request->userAgent(),

            'auth_id' => auth()->id(),

            'input' => $input,
        ];
    }

    /**
     * Log an exception with complete diagnostic information.
     */
    private function exceptionContext(Throwable $e): array
    {
        return [
            'exception' => get_class($e),

            'error' => $e->getMessage(),

            'file' => $e->getFile(),

            'line' => $e->getLine(),

            'code' => $e->getCode(),

            'trace' => $e->getTraceAsString(),
        ];
    }

    /**
     * Normalize role name.
     */
    private function normalizeRole(mixed $role): string
    {
        return strtoupper(
            trim((string) $role)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LIST USERS
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $startedAt = microtime(true);

        Log::info('USER INDEX: Request started', [
            ...$this->requestContext(),
        ]);

        try {

            $authUser = auth()->user();

            Log::debug('USER INDEX: Authenticated user resolved', [
                'auth_id' => $authUser?->id,
                'auth_email' => $authUser?->email,
                'auth_role' => $authUser?->role,
                'auth_level' => $authUser?->level,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Authorization
            |--------------------------------------------------------------------------
            */

            Log::debug('USER INDEX: Checking authorization', [
                'auth_id' => $authUser?->id,
                'ability' => 'viewAny',
                'model' => User::class,
            ]);

            $this->authorize('viewAny', User::class);

            Log::debug('USER INDEX: Authorization successful', [
                'auth_id' => $authUser?->id,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */

            $requestedPerPage = request()->input('per_page', 15);

            $perPage = min(
                max((int) $requestedPerPage, 1),
                100
            );

            Log::debug('USER INDEX: Pagination resolved', [
                'requested_per_page' => $requestedPerPage,
                'resolved_per_page' => $perPage,
                'page' => request()->input('page', 1),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Filters
            |--------------------------------------------------------------------------
            */

            $filters = [
                'exclude_id' => $authUser?->id,
                'search' => request()->input('search'),
                'role' => request()->input('role'),
                'level' => request()->input('level'),
                'is_active' => request()->input('is_active'),
            ];

            Log::debug('USER INDEX: Query filters', [
                'filters' => $filters,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Query
            |--------------------------------------------------------------------------
            */

            Log::debug('USER INDEX: Building UserQuery', [
                'auth_id' => $authUser?->id,
            ]);

            $paginator = UserQuery::make($authUser)
                ->apply($filters)
                ->withRelations()
                ->paginate($perPage);

            /*
            |--------------------------------------------------------------------------
            | Type Safety
            |--------------------------------------------------------------------------
            */

            if (!$paginator instanceof LengthAwarePaginator) {

                Log::critical('USER INDEX: Invalid paginator returned', [
                    'actual_type' => get_debug_type($paginator),
                    'auth_id' => $authUser?->id,
                ]);

                throw new \RuntimeException(
                    'Invalid paginator returned from UserQuery.'
                );
            }

            Log::info('USER INDEX: Users retrieved successfully', [
                'auth_id' => $authUser?->id,
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'execution_ms' => round(
                    (microtime(true) - $startedAt) * 1000,
                    2
                ),
            ]);

            return ApiResponse::success(
                UserResource::collection($paginator),
                'Users retrieved successfully',
                [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ]
            );

        } catch (Throwable $e) {

            Log::error('USER INDEX: Failed to fetch users', [
                ...$this->requestContext(),
                ...$this->exceptionContext($e),

                'execution_ms' => round(
                    (microtime(true) - $startedAt) * 1000,
                    2
                ),
            ]);

            return ApiResponse::error(
                'Failed to fetch users',
                config('app.debug')
                    ? $e->getMessage()
                    : 'An unexpected error occurred.',
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE USER
    |--------------------------------------------------------------------------
    */

    public function store(StoreUserRequest $request)
    {
        $startedAt = microtime(true);

        Log::info('USER STORE: Request started', [
            ...$this->requestContext($request),
        ]);

        try {

            /*
            |--------------------------------------------------------------------------
            | Base Authorization
            |--------------------------------------------------------------------------
            */

            Log::debug('USER STORE: Checking create authorization', [
                'auth_id' => auth()->id(),
                'ability' => 'create',
                'model' => User::class,
            ]);

            $this->authorize('create', User::class);

            Log::debug('USER STORE: Create authorization successful', [
                'auth_id' => auth()->id(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $validated = $request->validated();

            Log::debug('USER STORE: Request validation successful', [
                'auth_id' => auth()->id(),
                'fields' => array_keys($validated),
                'requested_role' => $validated['role'] ?? null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Normalize Role
            |--------------------------------------------------------------------------
            */

            if (isset($validated['role'])) {

                $originalRole = $validated['role'];

                $targetRole = $this->normalizeRole(
                    $validated['role']
                );

                Log::debug('USER STORE: Role normalized', [
                    'auth_id' => auth()->id(),
                    'original_role' => $originalRole,
                    'normalized_role' => $targetRole,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Ensure Requested Role Exists
                |--------------------------------------------------------------------------
                */

                Log::debug('USER STORE: Checking requested role', [
                    'role' => $targetRole,
                    'guard' => 'api',
                ]);

                $roleExists = Role::query()
                    ->where('name', $targetRole)
                    ->where('guard_name', 'api')
                    ->exists();

                Log::debug('USER STORE: Role existence check completed', [
                    'role' => $targetRole,
                    'guard' => 'api',
                    'exists' => $roleExists,
                ]);

                if (!$roleExists) {

                    Log::warning('USER STORE: Invalid role requested', [
                        'auth_id' => auth()->id(),
                        'requested_role' => $targetRole,
                    ]);

                    return ApiResponse::error(
                        'Invalid role.',
                        'The requested role does not exist.',
                        422
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Critical Privilege Check
                |--------------------------------------------------------------------------
                */

                Log::debug('USER STORE: Checking role assignment authorization', [
                    'auth_id' => auth()->id(),
                    'target_role' => $targetRole,
                ]);

                $this->authorize('assignRole', [
                    User::class,
                    $targetRole,
                ]);

                Log::info('USER STORE: Role assignment authorized', [
                    'auth_id' => auth()->id(),
                    'target_role' => $targetRole,
                ]);

                $validated['role'] = $targetRole;
            }

            /*
            |--------------------------------------------------------------------------
            | Create User
            |--------------------------------------------------------------------------
            */

            Log::info('USER STORE: Creating user', [
                'auth_id' => auth()->id(),
                'target_role' => $validated['role'] ?? null,
                'fields' => array_keys($validated),
            ]);

            $user = $this->userService->create($validated);

            Log::info('USER STORE: User created successfully', [
                'auth_id' => auth()->id(),
                'user_id' => $user->id,
                'target_role' => $validated['role'] ?? null,
                'execution_ms' => round(
                    (microtime(true) - $startedAt) * 1000,
                    2
                ),
            ]);

            return ApiResponse::created(
                new UserResource($user),
                'User created successfully'
            );

        } catch (Throwable $e) {

            Log::error('USER STORE: User creation failed', [
                ...$this->requestContext($request),
                ...$this->exceptionContext($e),

                'execution_ms' => round(
                    (microtime(true) - $startedAt) * 1000,
                    2
                ),
            ]);

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW USER
    |--------------------------------------------------------------------------
    */

    public function show(User $user)
    {
        $startedAt = microtime(true);

        Log::info('USER SHOW: Request started', [
            ...$this->requestContext(),

            'target_user_id' => $user->id,
        ]);

        try {

            Log::debug('USER SHOW: Checking authorization', [
                'auth_id' => auth()->id(),
                'target_user_id' => $user->id,
                'ability' => 'view',
            ]);

            $this->authorize('view', $user);

            Log::debug('USER SHOW: Authorization successful', [
                'auth_id' => auth()->id(),
                'target_user_id' => $user->id,
            ]);

            Log::debug('USER SHOW: Loading avatar relation', [
                'target_user_id' => $user->id,
            ]);

            $user->load('avatarFile');

            Log::info('USER SHOW: User retrieved successfully', [
                'auth_id' => auth()->id(),
                'target_user_id' => $user->id,
                'execution_ms' => round(
                    (microtime(true) - $startedAt) * 1000,
                    2
                ),
            ]);

            return ApiResponse::success([
                'id' => $user->id,

                'name' => $user->name,

                'email' => $user->email,

                'phone' => $user->phone ?? '',

                'role' => $user->role,

                'level' => $user->level,

                'sector_id' => $user->sector_id
                    ? (string) $user->sector_id
                    : '',

                'city_id' => $user->city_id
                    ? (string) $user->city_id
                    : '',

                'subcity_id' => $user->subcity_id
                    ? (string) $user->subcity_id
                    : '',

                'wereda_id' => $user->wereda_id
                    ? (string) $user->wereda_id
                    : '',

                'is_active' => (bool) $user->is_active,

                'avatar' => $user->avatar_file_id
                    ? "/private-file/{$user->avatar_file_id}"
                    : '',

                'created_at' => $user->created_at,

                'updated_at' => $user->updated_at,

            ], 'User retrieved successfully');

        } catch (Throwable $e) {

            Log::error('USER SHOW: User fetch failed', [
                ...$this->requestContext(),

                'target_user_id' => $user->id,

                ...$this->exceptionContext($e),

                'execution_ms' => round(
                    (microtime(true) - $startedAt) * 1000,
                    2
                ),
            ]);

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE USER
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateUserRequest $request,
        User $user
    ) {
        $startedAt = microtime(true);

        Log::info('USER UPDATE: Request started', [
            ...$this->requestContext($request),

            'target_user_id' => $user->id,

            'target_email' => $user->email,

            'target_role_before' => $user->role,
        ]);

        try {

            /*
            |--------------------------------------------------------------------------
            | Base Authorization
            |--------------------------------------------------------------------------
            */

            Log::debug('USER UPDATE: Checking update authorization', [
                'auth_id' => auth()->id(),
                'target_user_id' => $user->id,
                'ability' => 'update',
            ]);

            $this->authorize('update', $user);

            Log::debug('USER UPDATE: Update authorization successful', [
                'auth_id' => auth()->id(),
                'target_user_id' => $user->id,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $validated = $request->validated();

            Log::debug('USER UPDATE: Validation successful', [
                'auth_id' => auth()->id(),
                'target_user_id' => $user->id,
                'fields' => array_keys($validated),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Role Change Protection
            |--------------------------------------------------------------------------
            */

            $roleChanged = false;
            $oldRole = $user->role;
            $newRole = null;

            if (array_key_exists('role', $validated)) {

                $newRole = $this->normalizeRole(
                    $validated['role']
                );

                $roleChanged = $oldRole !== $newRole;

                Log::warning('USER UPDATE: Role change requested', [
                    'auth_id' => auth()->id(),
                    'target_user_id' => $user->id,
                    'old_role' => $oldRole,
                    'requested_role' => $validated['role'],
                    'normalized_role' => $newRole,
                    'role_changed' => $roleChanged,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Ensure Role Exists
                |--------------------------------------------------------------------------
                */

                Log::debug('USER UPDATE: Checking target role', [
                    'target_role' => $newRole,
                    'guard' => 'api',
                ]);

                $roleExists = Role::query()
                    ->where('name', $newRole)
                    ->where('guard_name', 'api')
                    ->exists();

                if (!$roleExists) {

                    Log::warning('USER UPDATE: Invalid target role', [
                        'auth_id' => auth()->id(),
                        'target_user_id' => $user->id,
                        'target_role' => $newRole,
                    ]);

                    return ApiResponse::error(
                        'Invalid role.',
                        'The requested role does not exist.',
                        422
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Critical Role Authorization
                |--------------------------------------------------------------------------
                */

                Log::debug('USER UPDATE: Checking role change authorization', [
                    'auth_id' => auth()->id(),
                    'target_user_id' => $user->id,
                    'target_role' => $newRole,
                ]);

                $this->authorize('updateRole', [
                    $user,
                    $newRole,
                ]);

                Log::info('USER UPDATE: Role change authorized', [
                    'auth_id' => auth()->id(),
                    'target_user_id' => $user->id,
                    'old_role' => $oldRole,
                    'new_role' => $newRole,
                ]);

                $validated['role'] = $newRole;
            }

            /*
            |--------------------------------------------------------------------------
            | Update User
            |--------------------------------------------------------------------------
            */

            Log::info('USER UPDATE: Updating user', [
                'auth_id' => auth()->id(),
                'target_user_id' => $user->id,
                'fields' => array_keys($validated),
                'role_changed' => $roleChanged,
                'old_role' => $oldRole,
                'new_role' => $newRole,
            ]);

            $updated = $this->userService->update(
                $user,
                $validated
            );

            Log::info('USER UPDATE: User updated successfully', [
                'auth_id' => auth()->id(),
                'target_user_id' => $updated->id,
                'role_changed' => $roleChanged,
                'old_role' => $oldRole,
                'new_role' => $updated->role,

                'execution_ms' => round(
                    (microtime(true) - $startedAt) * 1000,
                    2
                ),
            ]);

            return ApiResponse::success(
                new UserResource($updated),
                'User updated successfully'
            );

        } catch (Throwable $e) {

            Log::error('USER UPDATE: User update failed', [
                ...$this->requestContext($request),

                'target_user_id' => $user->id,

                'target_role' => $user->role,

                ...$this->exceptionContext($e),

                'execution_ms' => round(
                    (microtime(true) - $startedAt) * 1000,
                    2
                ),
            ]);

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE USER
    |--------------------------------------------------------------------------
    */

    public function destroy(User $user)
    {
        $startedAt = microtime(true);

        Log::warning('USER DELETE: Delete request started', [
            ...$this->requestContext(),

            'target_user_id' => $user->id,

            'target_email' => $user->email,

            'target_role' => $user->role,
        ]);

        try {

            Log::debug('USER DELETE: Checking delete authorization', [
                'auth_id' => auth()->id(),
                'target_user_id' => $user->id,
            ]);

            $this->authorize('delete', $user);

            Log::debug('USER DELETE: Delete authorization successful', [
                'auth_id' => auth()->id(),
                'target_user_id' => $user->id,
            ]);

            Log::warning('USER DELETE: Calling UserService delete', [
                'auth_id' => auth()->id(),
                'target_user_id' => $user->id,
            ]);

            $this->userService->delete($user);

            Log::warning('USER DELETE: User deleted successfully', [
                'auth_id' => auth()->id(),
                'user_id' => $user->id,
                'execution_ms' => round(
                    (microtime(true) - $startedAt) * 1000,
                    2
                ),
            ]);

            return ApiResponse::success(
                null,
                'User deleted successfully'
            );

        } catch (Throwable $e) {

            Log::error('USER DELETE: User deletion failed', [
                ...$this->requestContext(),

                'target_user_id' => $user->id,

                ...$this->exceptionContext($e),

                'execution_ms' => round(
                    (microtime(true) - $startedAt) * 1000,
                    2
                ),
            ]);

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PASSWORD
    |--------------------------------------------------------------------------
    */

    public function updatePassword(
        UpdatePasswordRequest $request,
        User $user
    ) {
        $startedAt = microtime(true);

        Log::info('USER PASSWORD: Password update request started', [
            ...$this->requestContext($request),

            'target_user_id' => $user->id,
        ]);

        try {

            Log::debug('USER PASSWORD: Checking authorization', [
                'auth_id' => auth()->id(),
                'target_user_id' => $user->id,
                'ability' => 'updatePassword',
            ]);

            $this->authorize(
                'updatePassword',
                $user
            );

            Log::debug('USER PASSWORD: Authorization successful', [
                'auth_id' => auth()->id(),
                'target_user_id' => $user->id,
            ]);

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT
            |--------------------------------------------------------------------------
            |
            | We intentionally DO NOT log validated password data.
            |
            */

            $validated = $request->validated();

            Log::debug('USER PASSWORD: Validation successful', [
                'auth_id' => auth()->id(),
                'target_user_id' => $user->id,
                'fields' => array_keys($validated),
            ]);

            Log::info('USER PASSWORD: Calling password update service', [
                'auth_id' => auth()->id(),
                'target_user_id' => $user->id,
            ]);

            $this->userService->updatePassword(
                $user,
                $validated
            );

            Log::info('USER PASSWORD: Password updated successfully', [
                'auth_id' => auth()->id(),
                'target_user_id' => $user->id,

                'execution_ms' => round(
                    (microtime(true) - $startedAt) * 1000,
                    2
                ),
            ]);

            return ApiResponse::success(
                null,
                'Password updated successfully'
            );

        } catch (Throwable $e) {

            Log::error('USER PASSWORD: Password update failed', [
                ...$this->requestContext($request),

                'target_user_id' => $user->id,

                ...$this->exceptionContext($e),

                'execution_ms' => round(
                    (microtime(true) - $startedAt) * 1000,
                    2
                ),
            ]);

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | TOGGLE USER STATUS
    |--------------------------------------------------------------------------
    */

    public function updateStatus(User $user)
    {
        $startedAt = microtime(true);

        $oldStatus = (bool) $user->is_active;

        Log::warning('USER STATUS: Status update request started', [
            ...$this->requestContext(),

            'target_user_id' => $user->id,

            'old_status' => $oldStatus,
        ]);

        try {

            Log::debug('USER STATUS: Checking authorization', [
                'auth_id' => auth()->id(),
                'target_user_id' => $user->id,
                'ability' => 'updateStatus',
            ]);

            $this->authorize(
                'updateStatus',
                $user
            );

            Log::debug('USER STATUS: Authorization successful', [
                'auth_id' => auth()->id(),
                'target_user_id' => $user->id,
            ]);

            Log::info('USER STATUS: Calling status toggle service', [
                'auth_id' => auth()->id(),
                'target_user_id' => $user->id,
                'old_status' => $oldStatus,
            ]);

            $updated = $this->userService->toggleStatus(
                $user
            );

            $newStatus = (bool) $updated->is_active;

            Log::warning('USER STATUS: Status updated successfully', [
                'auth_id' => auth()->id(),
                'user_id' => $updated->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,

                'execution_ms' => round(
                    (microtime(true) - $startedAt) * 1000,
                    2
                ),
            ]);

            return ApiResponse::success(
                new UserResource($updated),
                'User status updated successfully'
            );

        } catch (Throwable $e) {

            Log::error('USER STATUS: Status toggle failed', [
                ...$this->requestContext(),

                'target_user_id' => $user->id,

                'old_status' => $oldStatus,

                ...$this->exceptionContext($e),

                'execution_ms' => round(
                    (microtime(true) - $startedAt) * 1000,
                    2
                ),
            ]);

            throw $e;
        }
    }
}