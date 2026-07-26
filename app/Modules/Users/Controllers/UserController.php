<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Users\Services\UserService;
use App\Modules\Users\Requests\StoreUserRequest;
use App\Modules\Users\Requests\UpdateUserRequest;
use App\Modules\Users\Requests\UpdatePasswordRequest;
use App\Modules\Users\Resources\UserResource;
use App\Support\ApiResponse;
use App\Support\PaginatesResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Queries\UserQuery;


class UserController extends Controller
{
    use AuthorizesRequests, PaginatesResponse;

    public function __construct(
        protected UserService $userService
    ) {}

    public function index()
    {
        try {
            $authUser = auth()->user();
    
            $this->authorize('viewAny', User::class);
    
            Log::info('User index requested', [
                'auth_id' => $authUser?->id,
                'roles'   => $authUser?->getRoleNames(),
                'level'   => $authUser?->level,
            ]);
    
            $perPage = (int) request()->get('per_page', 15);
    
            // 🚀 SAFE QUERY PIPELINE
            $paginator = UserQuery::make($authUser)
                ->apply([
                    'exclude_id' => $authUser->id,
                    'search'     => request()->get('search'),
                    'role'       => request()->get('role'),
                    'level'      => request()->get('level'),
                    'is_active'  => request()->get('is_active'),
                ])
                ->withRelations()
                ->paginate($perPage);
    
            // ❗ ENSURE TYPE SAFETY (critical in production systems)
            if (!($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)) {
                throw new \RuntimeException(
                    'Invalid paginator returned from UserQuery: ' . get_class($paginator)
                );
            }
    
            // ✅ SINGLE RESPONSIBILITY RESPONSE LAYER
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
    
        } catch (\Throwable $e) {
    
            Log::error('Failed to fetch users', [
                'message' => $e->getMessage(),
                'auth_id' => auth()->id(),
                'request' => request()->all(),
            ]);
    
            return ApiResponse::error(
                'Failed to fetch users',
                $e->getMessage(),
                500
            );
        }
    }
    /**
     * CREATE USER
     */
    public function store(StoreUserRequest $request)
    {
        try {
            $this->authorize('create', User::class);

            Log::info('Creating user request', [
                'payload' => $request->validated(),
                'auth_id' => auth()->id(),
            ]);

            $user = $this->userService->create($request->validated());

            Log::info('User created successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return ApiResponse::created(
                new UserResource($user),
                'User created successfully'
            );

        } catch (Throwable $e) {

            Log::error('User creation failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->validated(),
                'auth_id' => auth()->id(),
            ]);

            throw $e;
        }
    }

    /**
     * SHOW USER
     */
    public function show(User $user)
    {
        try {
            $this->authorize('view', $user);

            Log::info('User detail requested', [
                'user_id' => $user->id,
                'auth_id' => auth()->id(),
            ]);

            $user->load('avatarFile');

            return ApiResponse::success([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',

                'role' => $user->role,
                'level' => $user->level,

                'sector_id' => $user->sector_id ? (string) $user->sector_id : '',
                'city_id' => $user->city_id ? (string) $user->city_id : '',
                'subcity_id' => $user->subcity_id ? (string) $user->subcity_id : '',
                'wereda_id' => $user->wereda_id ? (string) $user->wereda_id : '',

                'is_active' => (bool) $user->is_active,

                'avatar' => $user->avatar_file_id
                    ? "/private-file/{$user->avatar_file_id}"
                    : "",

                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ], 'User retrieved successfully');

        } catch (Throwable $e) {

            Log::error('User fetch failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'auth_id' => auth()->id(),
            ]);

            throw $e;
        }
    }

    /**
     * UPDATE USER
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            $this->authorize('update', $user);

            Log::info('Updating user', [
                'user_id' => $user->id,
                'payload' => $request->validated(),
                'auth_id' => auth()->id(),
            ]);

            $updated = $this->userService->update($user, $request->validated());

            Log::info('User updated successfully', [
                'user_id' => $updated->id,
            ]);

            return ApiResponse::success(
                new UserResource($updated),
                'User updated successfully'
            );

        } catch (Throwable $e) {

            Log::error('User update failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->validated(),
                'auth_id' => auth()->id(),
            ]);

            throw $e;
        }
    }

    /**
     * DELETE USER
     */
    public function destroy(User $user)
    {
        try {
            $this->authorize('delete', $user);

            Log::warning('Deleting user', [
                'user_id' => $user->id,
                'auth_id' => auth()->id(),
            ]);

            $this->userService->delete($user);

            Log::info('User deleted successfully', [
                'user_id' => $user->id,
            ]);

            return ApiResponse::success(
                null,
                'User deleted successfully'
            );

        } catch (Throwable $e) {

            Log::error('User deletion failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * UPDATE PASSWORD
     */
    public function updatePassword(UpdatePasswordRequest $request, User $user)
    {
        try {
            $this->authorize('updatePassword', $user);

            Log::info('Password update request', [
                'user_id' => $user->id,
                'auth_id' => auth()->id(),
            ]);

            $this->userService->updatePassword($user, $request->validated());

            Log::info('Password updated successfully', [
                'user_id' => $user->id,
            ]);

            return ApiResponse::success(
                null,
                'Password updated successfully'
            );

        } catch (Throwable $e) {

            Log::error('Password update failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * TOGGLE STATUS
     */
    public function updateStatus(User $user)
    {
        try {
            $this->authorize('updateStatus', $user);

            Log::info('Toggling user status', [
                'user_id' => $user->id,
                'auth_id' => auth()->id(),
                'current_status' => $user->is_active,
            ]);

            $updated = $this->userService->toggleStatus($user);

            Log::info('User status updated', [
                'user_id' => $updated->id,
                'new_status' => $updated->is_active,
            ]);

            return ApiResponse::success(
                new UserResource($updated),
                'User status updated successfully'
            );

        } catch (Throwable $e) {

            Log::error('Status toggle failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}