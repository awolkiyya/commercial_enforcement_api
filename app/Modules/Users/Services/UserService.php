<?php

namespace App\Modules\Users\Services;

use App\Models\User;
use App\Models\City;
use App\Services\Storage\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Services\Scopes\UniversalScopeEngine;



class UserService
{
    public function __construct(
        protected ImageService $imageService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | BASE QUERY
    |--------------------------------------------------------------------------
    */
    public function query(UniversalScopeEngine $scopeEngine)
    {
        $query = User::with([
            'roles',
            'permissions',
            'avatarFile',
            'city',
            'subcity',
            'wereda',
        ])->latest();
    
        /*
        |--------------------------------------------------------------------------
        | 🔐 UNIVERSAL SCOPE ENGINE (SECURITY LAYER)
        |--------------------------------------------------------------------------
        */
        $query = $scopeEngine->apply(
            $query,
            auth()->user(),
            []
        );
    
        /*
        |--------------------------------------------------------------------------
        | SEARCH (NON-SECURITY FILTER)
        |--------------------------------------------------------------------------
        */
        if ($search = request()->get('search')) {
    
            $query->where(function ($q) use ($search) {
    
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
    
        /*
        |--------------------------------------------------------------------------
        | ROLE FILTER (UI ONLY)
        |--------------------------------------------------------------------------
        */
        if ($role = request()->get('role')) {
            $query->where('role', $role);
        }
    
        /*
        |--------------------------------------------------------------------------
        | LEVEL FILTER (UI ONLY)
        |--------------------------------------------------------------------------
        */
        if ($level = request()->get('level')) {
            $query->where('level', $level);
        }
    
        /*
        |--------------------------------------------------------------------------
        | ACTIVE FILTER
        |--------------------------------------------------------------------------
        */
        if (!is_null(request()->get('is_active'))) {
            $query->where('is_active', request()->boolean('is_active'));
        }
    
        /*
        |--------------------------------------------------------------------------
        | ❌ REMOVE THESE (IMPORTANT)
        |--------------------------------------------------------------------------
        |
        | REMOVE:
        | city_id, subcity_id, wereda_id from request filtering
        |
        | They are now handled ONLY by UniversalScopeEngine
        |
        */
    
        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE USER
    |--------------------------------------------------------------------------
    */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {

            $roleLabel = $data['role'] ?? null;

            $data['password'] = Hash::make($data['password']);

            $data = $this->resolveGeography($data);

            $this->handleAvatarUpload($data);
            unset($data['avatar']);

            $user = User::create($data);

            $this->syncRole($user, $roleLabel);

            return $user->load(['roles', 'permissions', 'avatarFile', 'city', 'subcity', 'wereda']);
        });
    }
    /*
    |--------------------------------------------------------------------------
    | UPDATE USER
    |--------------------------------------------------------------------------
    */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {

            $roleLabel = $data['role'] ?? null;

            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $this->handleAvatarUpload($data, $user);
            unset($data['avatar']);

            // level may not be in the payload on update — fall back to existing user's level
            $data['level'] = $data['level'] ?? $user->level;
            $data = $this->resolveGeography($data);

            $user->update($data);

            if ($roleLabel) {
                $user->update(['role' => $roleLabel]);
            }

            $this->syncRole($user, $roleLabel);

            return $user->refresh()->load(['roles', 'permissions', 'avatarFile', 'city', 'subcity', 'wereda']);
        });
    }

    /**
 * Derive city_id/subcity_id from the most specific location
 * actually provided, based on the user's level. Falls back to
 * an explicit default only when nothing location-related was given.
 */
private function resolveGeography(array $data): array
{
    $level = $data['level'] ?? null;

    if ($level === 'WEREDA' && !empty($data['wereda_id'])) {
        $wereda = \App\Models\Wereda::with('subcity')->find($data['wereda_id']);

        if (!$wereda) {
            throw new \InvalidArgumentException('Invalid wereda_id provided.');
        }

        $data['subcity_id'] = $wereda->subcity_id;
        $data['city_id']    = $wereda->subcity?->city_id;

        return $data;
    }

    if ($level === 'SUBCITY' && !empty($data['subcity_id'])) {
        $subcity = \App\Models\Subcity::find($data['subcity_id']);

        if (!$subcity) {
            throw new \InvalidArgumentException('Invalid subcity_id provided.');
        }

        $data['city_id'] = $subcity->city_id;

        return $data;
    }

    if ($level === 'CITY') {

        $adamaCity = \App\Models\City::query()
            ->where('name', 'ADAMA')
            ->first();
    
    
        if (!$adamaCity) {
            throw new \InvalidArgumentException(
                'Default city ADAMA not found.'
            );
        }
    
    
        $data['city_id'] = $adamaCity->id;
    
    
        return $data;
    }

    // No level, or SUPER_ADMIN-style account with no geographic scope — leave as-is
    return $data;
}

    /*
    |--------------------------------------------------------------------------
    | DELETE USER
    |--------------------------------------------------------------------------
    */
    public function delete(User $user): bool
    {
        return DB::transaction(function () use ($user) {

            /**
             * DELETE AVATAR
             */
            if ($user->avatar_file_id) {

                $this->imageService->deleteById(
                    $user->avatar_file_id
                );
            }

            return $user->delete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | PASSWORD UPDATE
    |--------------------------------------------------------------------------
    */
    public function updatePassword(User $user, array $data): bool
    {
        return $user->update([
            'password' => Hash::make($data['password']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TOGGLE STATUS
    |--------------------------------------------------------------------------
    */
    public function toggleStatus(User $user): User
    {
        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return $user->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | ROLE SYNC
    |--------------------------------------------------------------------------
    */
    private function syncRole(User $user, ?string $roleLabel): void
    {
        if (!$roleLabel) {
            return;
        }

        $role = Role::where('name', $roleLabel)
            ->where('guard_name', 'api')
            ->first();

        if ($role) {

            $user->syncRoles([$role]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HANDLE AVATAR UPLOAD
    |--------------------------------------------------------------------------
    */
    private function handleAvatarUpload(
        array &$data,
        ?User $user = null
    ): void {
    
        if (
            empty($data['avatar']) ||
            !($data['avatar'] instanceof UploadedFile)
        ) {
            return;
        }
    
        $oldAvatarId = $user?->avatar_file_id;
    
        try {
            /**
             * UPLOAD NEW IMAGE
             */
            $uploaded = $this->imageService->uploadProfileImage(
                file: $data['avatar'],
                uploadedBy: auth()->id()
            );
    
            /**
             * HANDLE FAILURE EXPLICITLY
             */
            if (!$uploaded) {
                Log::error('Avatar upload failed in UserService', [
                    'user_id' => $user?->id,
                    'uploaded_by' => auth()->id(),
                    'file_name' => $data['avatar']->getClientOriginalName(),
                    'mime' => $data['avatar']->getMimeType(),
                    'size' => $data['avatar']->getSize(),
                ]);
    
                return;
            }
    
            /**
             * ATTACH NEW FILE
             */
            $data['avatar_file_id'] = $uploaded->id;
    
            /**
             * DELETE OLD FILE (safe)
             */
            if ($oldAvatarId) {
                try {
                    $this->imageService->deleteById($oldAvatarId);
                } catch (\Throwable $e) {
                    Log::warning('Failed to delete old avatar file', [
                        'old_avatar_id' => $oldAvatarId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
    
        } catch (\Throwable $e) {
    
            Log::error('Unexpected avatar upload exception', [
                'user_id' => $user?->id,
                'uploaded_by' => auth()->id() ?? 1,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}