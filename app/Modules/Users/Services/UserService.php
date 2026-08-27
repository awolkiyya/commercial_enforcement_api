<?php

namespace App\Modules\Users\Services;

use App\Models\User;
use App\Models\City;
use App\Models\SubCity;
use App\Models\Wereda;
use App\Services\Storage\ImageService;
use App\Services\Scopes\UniversalScopeEngine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

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
        | UNIVERSAL SECURITY SCOPE
        |--------------------------------------------------------------------------
        */

        $query = $scopeEngine->apply(
            $query,
            auth()->user(),
            []
        );

        /*
        |--------------------------------------------------------------------------
        | SEARCH
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
        | ROLE FILTER
        |--------------------------------------------------------------------------
        */

        if ($role = request()->get('role')) {
            $query->where('role', strtoupper(trim($role)));
        }

        /*
        |--------------------------------------------------------------------------
        | LEVEL FILTER
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
            $query->where(
                'is_active',
                request()->boolean('is_active')
            );
        }

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

            /*
            |--------------------------------------------------------------------------
            | NORMALIZE + VALIDATE ROLE
            |--------------------------------------------------------------------------
            */

            $roleLabel = $this->normalizeRole(
                $data['role'] ?? null
            );

            /*
            |--------------------------------------------------------------------------
            | DEFENSE-IN-DEPTH ROLE AUTHORIZATION
            |--------------------------------------------------------------------------
            |
            | Controller/Policy should already have checked this.
            | We check again at the service boundary so the service
            | cannot accidentally be used to bypass authorization.
            |--------------------------------------------------------------------------
            */

            if ($roleLabel !== null) {
                $this->authorizeRoleAssignment($roleLabel);
            }

            /*
            |--------------------------------------------------------------------------
            | PASSWORD
            |--------------------------------------------------------------------------
            */

            if (empty($data['password'])) {
                throw new InvalidArgumentException(
                    'Password is required.'
                );
            }

            $data['password'] = Hash::make(
                $data['password']
            );

            /*
            |--------------------------------------------------------------------------
            | GEOGRAPHY
            |--------------------------------------------------------------------------
            */

            $data = $this->resolveGeography($data);

            /*
            |--------------------------------------------------------------------------
            | AVATAR
            |--------------------------------------------------------------------------
            */

            $this->handleAvatarUpload($data);

            unset($data['avatar']);

            /*
            |--------------------------------------------------------------------------
            | ROLE FIELD
            |--------------------------------------------------------------------------
            |
            | Keep the users.role column synchronized with Spatie.
            |--------------------------------------------------------------------------
            */

            if ($roleLabel !== null) {
                $data['role'] = $roleLabel;
            }

            /*
            |--------------------------------------------------------------------------
            | CREATE USER
            |--------------------------------------------------------------------------
            */

            $user = User::create($data);

            /*
            |--------------------------------------------------------------------------
            | SYNC SPATIE ROLE
            |--------------------------------------------------------------------------
            */

            if ($roleLabel !== null) {
                $this->syncRole(
                    $user,
                    $roleLabel
                );
            }

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

    public function update(
        User $user,
        array $data
    ): User {
        return DB::transaction(function () use ($user, $data) {

            /*
            |--------------------------------------------------------------------------
            | ROLE CHANGE DETECTION
            |--------------------------------------------------------------------------
            */

            $roleWasProvided = array_key_exists(
                'role',
                $data
            );

            $roleLabel = $roleWasProvided
                ? $this->normalizeRole($data['role'])
                : null;

            /*
            |--------------------------------------------------------------------------
            | ROLE CHANGE AUTHORIZATION
            |--------------------------------------------------------------------------
            |
            | Controller/Policy performs the primary authorization.
            | Service performs the defense-in-depth authorization.
            |--------------------------------------------------------------------------
            */

            if ($roleWasProvided) {

                if ($roleLabel === null) {
                    throw new InvalidArgumentException(
                        'A valid target role is required.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Prevent self-role modification
                |--------------------------------------------------------------------------
                */

                $actor = $this->authenticatedUser();

                if ($actor->id === $user->id) {
                    throw new AccessDeniedHttpException(
                        'You cannot change your own role.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Target SUPER_ADMIN protection
                |--------------------------------------------------------------------------
                */

                if (
                    $user->hasRole('SUPER_ADMIN')
                    && !$actor->hasRole('SUPER_ADMIN')
                ) {
                    throw new AccessDeniedHttpException(
                        'You are not authorized to modify a SUPER_ADMIN account.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Hierarchy authorization
                |--------------------------------------------------------------------------
                */

                $this->authorizeRoleAssignment(
                    $roleLabel,
                    $user
                );
            }

            /*
            |--------------------------------------------------------------------------
            | PASSWORD
            |--------------------------------------------------------------------------
            */

            if (!empty($data['password'])) {

                $data['password'] = Hash::make(
                    $data['password']
                );

            } else {

                unset($data['password']);
            }

            /*
            |--------------------------------------------------------------------------
            | AVATAR
            |--------------------------------------------------------------------------
            */

            $this->handleAvatarUpload(
                $data,
                $user
            );

            unset($data['avatar']);

            /*
            |--------------------------------------------------------------------------
            | LEVEL
            |--------------------------------------------------------------------------
            */

            if (!array_key_exists('level', $data)) {
                $data['level'] = $user->level;
            }

            /*
            |--------------------------------------------------------------------------
            | GEOGRAPHY
            |--------------------------------------------------------------------------
            */

            $data = $this->resolveGeography(
                $data,
                $user
            );

            /*
            |--------------------------------------------------------------------------
            | ROLE
            |--------------------------------------------------------------------------
            |
            | Never allow arbitrary role data to pass through.
            |--------------------------------------------------------------------------
            */

            if ($roleWasProvided) {
                $data['role'] = $roleLabel;
            } else {
                unset($data['role']);
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE USER
            |--------------------------------------------------------------------------
            */

            $user->update($data);

            /*
            |--------------------------------------------------------------------------
            | SYNC SPATIE ROLE
            |--------------------------------------------------------------------------
            */

            if ($roleWasProvided) {
                $this->syncRole(
                    $user,
                    $roleLabel
                );
            }

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
    | ROLE AUTHORIZATION
    |--------------------------------------------------------------------------
    |
    | DEFENSE-IN-DEPTH SECURITY CONTROL
    |--------------------------------------------------------------------------
    |
    | Hierarchy:
    |
    | SUPER_ADMIN
    |      ├── ADMIN
    |      ├── SUPERVISOR
    |      └── INSPECTOR
    |
    | ADMIN
    |      ├── SUPERVISOR
    |      └── INSPECTOR
    |
    | SUPERVISOR
    |      └── NONE
    |
    | INSPECTOR
    |      └── NONE
    |
    |--------------------------------------------------------------------------
    */

    private function authorizeRoleAssignment(
        string $targetRole,
        ?User $targetUser = null
    ): void {
        $actor = $this->authenticatedUser();

        /*
        |--------------------------------------------------------------------------
        | Normalize target role
        |--------------------------------------------------------------------------
        */

        $targetRole = strtoupper(
            trim($targetRole)
        );

        /*
        |--------------------------------------------------------------------------
        | Explicit role assignment permission
        |--------------------------------------------------------------------------
        */

        if (!$actor->can('users.assign_role')) {

            $this->denyRoleAssignment(
                $actor,
                $targetRole,
                $targetUser,
                'Missing users.assign_role permission.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SUPER_ADMIN
        |--------------------------------------------------------------------------
        |
        | SUPER_ADMIN can assign:
        |
        | ADMIN
        | SUPERVISOR
        | INSPECTOR
        |
        | SUPER_ADMIN cannot assign:
        |
        | SUPER_ADMIN
        |--------------------------------------------------------------------------
        */

        if ($actor->hasRole('SUPER_ADMIN')) {

            if (!in_array($targetRole, [
                'ADMIN',
                'SUPERVISOR',
                'INSPECTOR',
            ], true)) {

                $this->denyRoleAssignment(
                    $actor,
                    $targetRole,
                    $targetUser
                );
            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | ADMIN
        |--------------------------------------------------------------------------
        |
        | ADMIN can assign:
        |
        | SUPERVISOR
        | INSPECTOR
        |
        | ADMIN cannot assign:
        |
        | ADMIN
        | SUPER_ADMIN
        |--------------------------------------------------------------------------
        */

        if ($actor->hasRole('ADMIN')) {

            if (!in_array($targetRole, [
                'SUPERVISOR',
                'INSPECTOR',
            ], true)) {

                $this->denyRoleAssignment(
                    $actor,
                    $targetRole,
                    $targetUser
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Existing target must belong to same city
            |--------------------------------------------------------------------------
            */

            if (
                $targetUser !== null
                && !$this->sameCity(
                    $actor,
                    $targetUser
                )
            ) {
                $this->denyRoleAssignment(
                    $actor,
                    $targetRole,
                    $targetUser,
                    'Target user is outside the administrator city.'
                );
            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | SUPERVISOR
        |--------------------------------------------------------------------------
        */

        if ($actor->hasRole('SUPERVISOR')) {

            $this->denyRoleAssignment(
                $actor,
                $targetRole,
                $targetUser
            );
        }

        /*
        |--------------------------------------------------------------------------
        | INSPECTOR
        |--------------------------------------------------------------------------
        */

        if ($actor->hasRole('INSPECTOR')) {

            $this->denyRoleAssignment(
                $actor,
                $targetRole,
                $targetUser
            );
        }

        /*
        |--------------------------------------------------------------------------
        | UNKNOWN ROLE
        |--------------------------------------------------------------------------
        */

        $this->denyRoleAssignment(
            $actor,
            $targetRole,
            $targetUser
        );
    }

    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATED USER
    |--------------------------------------------------------------------------
    */

    private function authenticatedUser(): User
    {
        $actor = auth()->user();

        if (!$actor instanceof User) {
            throw new UnauthorizedHttpException(
                'Bearer',
                'Authentication required.'
            );
        }

        return $actor;
    }

    /*
    |--------------------------------------------------------------------------
    | DENY ROLE ASSIGNMENT
    |--------------------------------------------------------------------------
    */

    private function denyRoleAssignment(
        User $actor,
        string $targetRole,
        ?User $targetUser = null,
        string $reason = 'You are not authorized to assign this role.'
    ): never {
        Log::warning(
            'Blocked privilege escalation attempt',
            [
                'actor_id' => $actor->id,
                'actor_roles' => $actor->getRoleNames(),
                'target_role' => $targetRole,
                'target_user_id' => $targetUser?->id,
                'reason' => $reason,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]
        );

        throw new AccessDeniedHttpException($reason);
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE ROLE
    |--------------------------------------------------------------------------
    */

    private function normalizeRole(
        mixed $role
    ): ?string {
        if ($role === null) {
            return null;
        }

        if (!is_string($role)) {
            throw new InvalidArgumentException(
                'Invalid role value.'
            );
        }

        $role = strtoupper(
            trim($role)
        );

        if ($role === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Only application roles are accepted
        |--------------------------------------------------------------------------
        */

        $allowedRoles = [
            'SUPER_ADMIN',
            'ADMIN',
            'SUPERVISOR',
            'INSPECTOR',
        ];

        if (!in_array($role, $allowedRoles, true)) {
            throw new InvalidArgumentException(
                'Invalid role specified.'
            );
        }

        return $role;
    }

    /*
    |--------------------------------------------------------------------------
    | GEOGRAPHY RESOLUTION
    |--------------------------------------------------------------------------
    */

    private function resolveGeography(
        array $data,
        ?User $existingUser = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Determine level
        |--------------------------------------------------------------------------
        |
        | During PATCH/partial updates, retain the existing level when
        | no new level is supplied.
        |--------------------------------------------------------------------------
        */

        $level = $data['level']
            ?? $existingUser?->level;

        /*
        |--------------------------------------------------------------------------
        | WEREDA
        |--------------------------------------------------------------------------
        */

        if ($level === 'WEREDA') {

            $weredaId = $data['wereda_id']
                ?? $existingUser?->wereda_id;

            if (empty($weredaId)) {
                throw new InvalidArgumentException(
                    'wereda_id is required for WEREDA level.'
                );
            }

            $wereda = Wereda::with(
                'subcity'
            )->find($weredaId);

            if (!$wereda) {
                throw new InvalidArgumentException(
                    'Invalid wereda_id provided.'
                );
            }

            if (!$wereda->subcity) {
                throw new InvalidArgumentException(
                    'The selected wereda is not associated with a subcity.'
                );
            }

            $data['wereda_id'] =
                $wereda->id;

            $data['subcity_id'] =
                $wereda->subcity_id;

            $data['city_id'] =
                $wereda->subcity->city_id;

            return $data;
        }

        /*
        |--------------------------------------------------------------------------
        | SUBCITY
        |--------------------------------------------------------------------------
        */

        if ($level === 'SUBCITY') {

            $subcityId = $data['subcity_id']
                ?? $existingUser?->subcity_id;

            if (empty($subcityId)) {
                throw new InvalidArgumentException(
                    'subcity_id is required for SUBCITY level.'
                );
            }

            $subcity = SubCity::find(
                $subcityId
            );

            if (!$subcity) {
                throw new InvalidArgumentException(
                    'Invalid subcity_id provided.'
                );
            }

            $data['subcity_id'] =
                $subcity->id;

            $data['city_id'] =
                $subcity->city_id;

            /*
            |--------------------------------------------------------------------------
            | A SUBCITY user must not have a WEREDA assignment
            |--------------------------------------------------------------------------
            */

            $data['wereda_id'] = null;

            return $data;
        }

        /*
        |--------------------------------------------------------------------------
        | CITY
        |--------------------------------------------------------------------------
        */

        if ($level === 'CITY') {

            /*
            |--------------------------------------------------------------------------
            | Your application currently uses ADAMA as the city.
            |--------------------------------------------------------------------------
            */

            $adamaCity = City::query()
                ->where('name', 'ADAMA')
                ->first();

            if (!$adamaCity) {
                throw new InvalidArgumentException(
                    'Default city ADAMA not found.'
                );
            }

            $data['city_id'] =
                $adamaCity->id;

            /*
            |--------------------------------------------------------------------------
            | CITY-level users must not have lower geography assignments
            |--------------------------------------------------------------------------
            */

            $data['subcity_id'] = null;
            $data['wereda_id'] = null;

            return $data;
        }

        /*
        |--------------------------------------------------------------------------
        | GLOBAL / SUPER_ADMIN
        |--------------------------------------------------------------------------
        */

        if ($level === 'GLOBAL') {

            $data['city_id'] = null;
            $data['subcity_id'] = null;
            $data['wereda_id'] = null;

            return $data;
        }

        /*
        |--------------------------------------------------------------------------
        | If level is unchanged/unknown, don't manufacture geography.
        |--------------------------------------------------------------------------
        */

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE USER
    |--------------------------------------------------------------------------
    */

    public function delete(
        User $user
    ): bool {
        return DB::transaction(
            function () use ($user) {

                /*
                |--------------------------------------------------------------------------
                | Defensive self-delete protection
                |--------------------------------------------------------------------------
                */

                if (
                    auth()->check()
                    && auth()->id() === $user->id
                ) {
                    throw new AccessDeniedHttpException(
                        'You cannot delete your own account.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Delete avatar
                |--------------------------------------------------------------------------
                */

                if ($user->avatar_file_id) {

                    $this->imageService->deleteById(
                        $user->avatar_file_id
                    );
                }

                return (bool) $user->delete();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PASSWORD UPDATE
    |--------------------------------------------------------------------------
    */

    public function updatePassword(
        User $user,
        array $data
    ): bool {
        if (empty($data['password'])) {
            throw new InvalidArgumentException(
                'Password is required.'
            );
        }

        return $user->update([
            'password' => Hash::make(
                $data['password']
            ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TOGGLE STATUS
    |--------------------------------------------------------------------------
    */

    public function toggleStatus(
        User $user
    ): User {
        $actor = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Defensive self-disable protection
        |--------------------------------------------------------------------------
        |
        | Policy already prevents SUPER_ADMIN from changing their own
        | status. This protects the service if it is called elsewhere.
        |--------------------------------------------------------------------------
        */

        if (
            $actor instanceof User
            && $actor->id === $user->id
        ) {
            throw new AccessDeniedHttpException(
                'You cannot change your own account status.'
            );
        }

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

    private function syncRole(
        User $user,
        string $roleLabel
    ): void {
        $role = Role::query()
            ->where('name', $roleLabel)
            ->where('guard_name', 'api')
            ->first();

        if (!$role) {
            throw new RuntimeException(
                "Role [{$roleLabel}] does not exist."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Keep Spatie permissions synchronized
        |--------------------------------------------------------------------------
        */

        $user->syncRoles([
            $role,
        ]);
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
            empty($data['avatar'])
            || !(
                $data['avatar']
                instanceof UploadedFile
            )
        ) {
            return;
        }

        $oldAvatarId =
            $user?->avatar_file_id;

        try {

            $uploaded =
                $this->imageService->uploadProfileImage(
                    file: $data['avatar'],
                    uploadedBy: auth()->id()
                );

            if (!$uploaded) {

                Log::error(
                    'Avatar upload failed in UserService',
                    [
                        'user_id' =>
                            $user?->id,

                        'uploaded_by' =>
                            auth()->id(),

                        'file_name' =>
                            $data['avatar']
                                ->getClientOriginalName(),

                        'mime' =>
                            $data['avatar']
                                ->getMimeType(),

                        'size' =>
                            $data['avatar']
                                ->getSize(),
                    ]
                );

                return;
            }

            $data['avatar_file_id'] =
                $uploaded->id;

            /*
            |--------------------------------------------------------------------------
            | Delete previous avatar
            |--------------------------------------------------------------------------
            */

            if ($oldAvatarId) {

                try {

                    $this->imageService
                        ->deleteById(
                            $oldAvatarId
                        );

                } catch (\Throwable $e) {

                    Log::warning(
                        'Failed to delete old avatar file',
                        [
                            'old_avatar_id' =>
                                $oldAvatarId,

                            'error' =>
                                $e->getMessage(),
                        ]
                    );
                }
            }

        } catch (\Throwable $e) {

            Log::error(
                'Unexpected avatar upload exception',
                [
                    'user_id' =>
                        $user?->id,

                    'uploaded_by' =>
                        auth()->id(),

                    'error' =>
                        $e->getMessage(),

                    'trace' =>
                        $e->getTraceAsString(),
                ]
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SAME CITY
    |--------------------------------------------------------------------------
    */

    private function sameCity(
        User $user,
        User $model
    ): bool {
        return !empty($user->city_id)
            && !empty($model->city_id)
            && (string) $user->city_id
                === (string) $model->city_id;
    }
}