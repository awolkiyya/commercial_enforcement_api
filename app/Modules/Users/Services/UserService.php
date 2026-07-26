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

            /**
             * PASSWORD HASH
             */
            $data['password'] = Hash::make($data['password']);

            /**
             * CITY ASSIGNMENT (SAFE + EXPLICIT)
             */
            $data['city_id'] = $data['city_id']
                ?? config('system.default_city_id')
                ?? City::query()->value('id');

            /**
             * HANDLE AVATAR
             */
            $this->handleAvatarUpload($data);

            unset($data['avatar']);

            /**
             * CREATE USER
             */
            $user = User::create($data);

            /**
             * ROLE SYNC
             */
            $this->syncRole($user, $roleLabel);

            return $user->load([
                'roles',
                'permissions',
                'avatarFile',
                'city',
                'subcity',
                'wereda',
            ]);
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
    
            /**
             * PASSWORD HANDLING
             */
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }
    
            /**
             * AVATAR HANDLING
             */
            $this->handleAvatarUpload($data, $user);
            unset($data['avatar']);
    
            /**
             * =========================
             * CITY AUTO-ASSIGN LOGIC
             * =========================
             */
    
            if (!isset($data['city_id']) || empty($data['city_id'])) {
    
                // fallback from DB (safe default city)
                $data['city_id'] = \App\Models\City::query()->value('id');
    
            } else {
    
                // validate provided city_id
                $validCity = \App\Models\City::query()
                    ->where('id', $data['city_id'])
                    ->value('id');
    
                if (!$validCity) {
                    throw new \InvalidArgumentException("Invalid city_id provided.");
                }
    
                $data['city_id'] = $validCity;
            }
    
            /**
             * UPDATE USER
             */
            $user->update($data);
    
            /**
             * ROLE SYNC
             */
            if ($roleLabel) {
                $user->update(['role' => $roleLabel]);
            }
    
            $this->syncRole($user, $roleLabel);
    
            return $user->refresh()->load([
                'roles',
                'permissions',
                'avatarFile',
                'city',
                'subcity',
                'wereda',
            ]);
        });
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