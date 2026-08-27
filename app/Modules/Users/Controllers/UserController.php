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
    | LIST USERS
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        try {
            $authUser = auth()->user();

            /*
            |--------------------------------------------------------------------------
            | Authorization
            |--------------------------------------------------------------------------
            */
            $this->authorize('viewAny', User::class);

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */
            $perPage = min(
                max((int) request()->input('per_page', 15), 1),
                100
            );

            /*
            |--------------------------------------------------------------------------
            | Query
            |--------------------------------------------------------------------------
            */
            $paginator = UserQuery::make($authUser)
                ->apply([
                    'exclude_id' => $authUser->id,
                    'search'     => request()->input('search'),
                    'role'       => request()->input('role'),
                    'level'      => request()->input('level'),
                    'is_active'  => request()->input('is_active'),
                ])
                ->withRelations()
                ->paginate($perPage);

            /*
            |--------------------------------------------------------------------------
            | Type Safety
            |--------------------------------------------------------------------------
            */
            if (!$paginator instanceof LengthAwarePaginator) {
                throw new \RuntimeException(
                    'Invalid paginator returned from UserQuery.'
                );
            }

            return ApiResponse::success(
                UserResource::collection($paginator),
                'Users retrieved successfully',
                [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
                ]
            );

        } catch (Throwable $e) {

            Log::error('Failed to fetch users', [
                'auth_id' => auth()->id(),
                'error'   => $e->getMessage(),
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
    |
    | SECURITY:
    |
    | 1. create() verifies that the actor can create users.
    | 2. assignRole() verifies that the actor can assign the requested role.
    | 3. The role must exist in the API guard.
    | 4. UserService must independently enforce the same hierarchy.
    |
    |--------------------------------------------------------------------------
    */
    public function store(StoreUserRequest $request)
    {
        try {
            /*
            |--------------------------------------------------------------------------
            | Base authorization
            |--------------------------------------------------------------------------
            */
            $this->authorize('create', User::class);

            $validated = $request->validated();

            /*
            |--------------------------------------------------------------------------
            | Normalize role
            |--------------------------------------------------------------------------
            */
            if (isset($validated['role'])) {

                $targetRole = strtoupper(
                    trim((string) $validated['role'])
                );

                /*
                |--------------------------------------------------------------------------
                | Ensure requested role exists
                |--------------------------------------------------------------------------
                */
                $roleExists = Role::query()
                    ->where('name', $targetRole)
                    ->where('guard_name', 'api')
                    ->exists();

                if (!$roleExists) {
                    return ApiResponse::error(
                        'Invalid role.',
                        'The requested role does not exist.',
                        422
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | CRITICAL PRIVILEGE CHECK
                |--------------------------------------------------------------------------
                |
                | Prevent:
                |
                | ADMIN      → SUPER_ADMIN
                | SUPERVISOR → any privileged role
                | INSPECTOR  → any privileged role
                |
                |--------------------------------------------------------------------------
                */
                $this->authorize('assignRole', [
                    User::class,
                    $targetRole,
                ]);

                $validated['role'] = $targetRole;
            }

            /*
            |--------------------------------------------------------------------------
            | Create user
            |--------------------------------------------------------------------------
            */
            $user = $this->userService->create($validated);

            Log::info('User created successfully', [
                'auth_id'     => auth()->id(),
                'user_id'     => $user->id,
                'target_role' => $validated['role'] ?? null,
            ]);

            return ApiResponse::created(
                new UserResource($user),
                'User created successfully'
            );

        } catch (Throwable $e) {

            Log::error('User creation failed', [
                'auth_id' => auth()->id(),
                'error'   => $e->getMessage(),
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
        try {
            /*
            |--------------------------------------------------------------------------
            | Authorization
            |--------------------------------------------------------------------------
            */
            $this->authorize('view', $user);

            $user->load('avatarFile');

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

            Log::error('User fetch failed', [
                'auth_id' => auth()->id(),
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE USER
    |--------------------------------------------------------------------------
    |
    | SECURITY:
    |
    | Normal account update:
    |     → update()
    |
    | Role change:
    |     → updateRole()
    |
    | This prevents the generic update endpoint from becoming a
    | privilege-escalation bypass.
    |
    |--------------------------------------------------------------------------
    */
    public function update(
        UpdateUserRequest $request,
        User $user
    ) {
        try {
            /*
            |--------------------------------------------------------------------------
            | Base authorization
            |--------------------------------------------------------------------------
            */
            $this->authorize('update', $user);

            $validated = $request->validated();

            /*
            |--------------------------------------------------------------------------
            | ROLE CHANGE PROTECTION
            |--------------------------------------------------------------------------
            */
            if (array_key_exists('role', $validated)) {

                $targetRole = strtoupper(
                    trim((string) $validated['role'])
                );

                /*
                |--------------------------------------------------------------------------
                | Ensure role exists
                |--------------------------------------------------------------------------
                */
                $roleExists = Role::query()
                    ->where('name', $targetRole)
                    ->where('guard_name', 'api')
                    ->exists();

                if (!$roleExists) {
                    return ApiResponse::error(
                        'Invalid role.',
                        'The requested role does not exist.',
                        422
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | CRITICAL ROLE AUTHORIZATION
                |--------------------------------------------------------------------------
                */
                $this->authorize('updateRole', [
                    $user,
                    $targetRole,
                ]);

                $validated['role'] = $targetRole;
            }

            /*
            |--------------------------------------------------------------------------
            | Update user
            |--------------------------------------------------------------------------
            */
            $updated = $this->userService->update(
                $user,
                $validated
            );

            Log::info('User updated successfully', [
                'auth_id' => auth()->id(),
                'user_id' => $updated->id,
                'role_changed' => array_key_exists(
                    'role',
                    $validated
                ),
            ]);

            return ApiResponse::success(
                new UserResource($updated),
                'User updated successfully'
            );

        } catch (Throwable $e) {

            Log::error('User update failed', [
                'auth_id' => auth()->id(),
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
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
        try {
            $this->authorize('delete', $user);

            $this->userService->delete($user);

            Log::warning('User deleted successfully', [
                'auth_id' => auth()->id(),
                'user_id' => $user->id,
            ]);

            return ApiResponse::success(
                null,
                'User deleted successfully'
            );

        } catch (Throwable $e) {

            Log::error('User deletion failed', [
                'auth_id' => auth()->id(),
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
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
        try {
            $this->authorize(
                'updatePassword',
                $user
            );

            $this->userService->updatePassword(
                $user,
                $request->validated()
            );

            Log::info('Password updated successfully', [
                'auth_id' => auth()->id(),
                'user_id' => $user->id,
            ]);

            return ApiResponse::success(
                null,
                'Password updated successfully'
            );

        } catch (Throwable $e) {

            Log::error('Password update failed', [
                'auth_id' => auth()->id(),
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
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
        try {
            $this->authorize(
                'updateStatus',
                $user
            );

            $updated = $this->userService->toggleStatus(
                $user
            );

            Log::info('User status updated successfully', [
                'auth_id'     => auth()->id(),
                'user_id'     => $updated->id,
                'new_status'  => (bool) $updated->is_active,
            ]);

            return ApiResponse::success(
                new UserResource($updated),
                'User status updated successfully'
            );

        } catch (Throwable $e) {

            Log::error('Status toggle failed', [
                'auth_id' => auth()->id(),
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}