<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksHierarchy;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Guard;

class UserPolicy
{
    use ChecksHierarchy;

    /*
    |--------------------------------------------------------------------------
    | LOGGING HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Build a safe authorization context.
     *
     * IMPORTANT:
     * Never log passwords, tokens, cookies, or authentication secrets.
     */
    private function authContext(User $user): array
    {
        return [
            'auth_user_id' => $user->id,
            'auth_email' => $user->email,
            'auth_role_column' => $user->role,
            'auth_level' => $user->level,

            'spatie_roles' => $user->getRoleNames()->toArray(),

            'city_id' => $user->city_id,
            'subcity_id' => $user->subcity_id,
            'wereda_id' => $user->wereda_id,
        ];
    }

    /**
     * Log a policy decision.
     */
    private function logDecision(
        string $ability,
        User $user,
        bool $allowed,
        array $context = []
    ): void {
        Log::info(
            'USER POLICY: Authorization decision',
            [
                'ability' => $ability,
                'allowed' => $allowed,

                ...$this->authContext($user),

                ...$context,
            ]
        );
    }

    /**
     * Safely resolve the guard name(s) for a model, for DIAGNOSTIC
     * logging only. This value is never used in an authorization
     * decision.
     *
     * IMPORTANT:
     * `App\Models\User::getGuardNames()` does NOT exist. It is not an
     * Eloquent method and it is not part of Spatie's `HasRoles` trait.
     * The correct Spatie API is the static
     * `Spatie\Permission\Guard::getNames($modelOrClass)` helper.
     *
     * This method is wrapped in a try/catch so that a logging-only
     * diagnostic can NEVER take down a real authorization request,
     * regardless of future package upgrades or refactors.
     */
    private function safeGuardNames(User $user): ?array
    {
        try {
            return Guard::getNames($user)->toArray();
        } catch (\Throwable $e) {

            Log::debug(
                'USER POLICY: Failed to resolve guard names (diagnostic only)',
                [
                    'auth_user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]
            );

            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SPATIE PERMISSION HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Check a permission explicitly against the API guard.
     *
     * We intentionally do NOT use:
     *
     *     $user->can('users.assign_role')
     *
     * because in this application the User model / Gate context can resolve
     * the permission using a different guard than the user's Spatie API role.
     *
     * The logs showed:
     *
     *     has_permission_via_can = false
     *     has_permission_direct_api = true
     *
     * Therefore the explicit API guard check is the correct check here.
     */
    private function hasApiPermission(
        User $user,
        string $permission
    ): bool {
        try {
            return $user->hasPermissionTo(
                $permission,
                'api'
            );
        } catch (\Throwable $e) {

            Log::error(
                'USER POLICY: API permission check failed',
                [
                    'auth_user_id' => $user->id,
                    'auth_email' => $user->email,
                    'permission' => $permission,
                    'guard' => 'api',
                    'exception' => get_class($e),
                    'error' => $e->getMessage(),
                ]
            );

            return false;
        }
    }

    /**
     * Check whether the target role is assignable.
     *
     * This delegates the actual role hierarchy rules to ChecksHierarchy.
     */
    private function checkRoleHierarchy(
        User $user,
        string $targetRole
    ): bool {
        return $this->canAssignRole(
            $user,
            strtoupper(trim($targetRole))
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VIEW USER LIST
    |--------------------------------------------------------------------------
    */

    public function viewAny(User $user): bool
    {
        $isSystemAdmin = $this->isSystemAdmin($user);
        $isAdmin = $this->isAdmin($user);

        $allowed = $isSystemAdmin || $isAdmin;

        $this->logDecision(
            'viewAny',
            $user,
            $allowed,
            [
                'is_system_admin' => $isSystemAdmin,
                'is_admin' => $isAdmin,

                'reason' => $allowed
                    ? 'SUPER_ADMIN or ADMIN'
                    : 'User is not SUPER_ADMIN or ADMIN',
            ]
        );

        return $allowed;
    }

    /*
    |--------------------------------------------------------------------------
    | VIEW SINGLE USER
    |--------------------------------------------------------------------------
    */

    public function view(
        User $user,
        User $model
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |--------------------------------------------------------------------------
        */

        if ($this->isSystemAdmin($user)) {

            $this->logDecision(
                'view',
                $user,
                true,
                [
                    'target_user_id' => $model->id,
                    'target_role' => $model->role,
                    'reason' => 'SUPER_ADMIN global access',
                ]
            );

            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | ADMIN → SAME CITY
        |--------------------------------------------------------------------------
        */

        $isAdmin = $this->isAdmin($user);
        $sameCity = $this->sameCity($user, $model);

        if ($isAdmin && $sameCity) {

            $this->logDecision(
                'view',
                $user,
                true,
                [
                    'target_user_id' => $model->id,
                    'target_role' => $model->role,
                    'reason' => 'ADMIN same city',
                    'same_city' => true,
                ]
            );

            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | SUPERVISOR → SAME SUBCITY
        |--------------------------------------------------------------------------
        */

        $isSupervisor = $this->isSupervisor($user);
        $sameSubCity = $this->sameSubCity($user, $model);

        if ($isSupervisor && $sameSubCity) {

            $this->logDecision(
                'view',
                $user,
                true,
                [
                    'target_user_id' => $model->id,
                    'target_role' => $model->role,
                    'reason' => 'SUPERVISOR same subcity',
                    'same_subcity' => true,
                ]
            );

            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | INSPECTOR → SAME WEREDA
        |--------------------------------------------------------------------------
        */

        $isInspector = $this->isInspector($user);
        $sameWereda = $this->sameWereda($user, $model);

        if ($isInspector && $sameWereda) {

            $this->logDecision(
                'view',
                $user,
                true,
                [
                    'target_user_id' => $model->id,
                    'target_role' => $model->role,
                    'reason' => 'INSPECTOR same wereda',
                    'same_wereda' => true,
                ]
            );

            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | DENIED
        |--------------------------------------------------------------------------
        */

        $this->logDecision(
            'view',
            $user,
            false,
            [
                'target_user_id' => $model->id,
                'target_role' => $model->role,

                'is_admin' => $isAdmin,
                'is_supervisor' => $isSupervisor,
                'is_inspector' => $isInspector,

                'same_city' => $sameCity,
                'same_subcity' => $sameSubCity,
                'same_wereda' => $sameWereda,

                'target_city_id' => $model->city_id,
                'target_subcity_id' => $model->subcity_id,
                'target_wereda_id' => $model->wereda_id,

                'reason' => 'No applicable scope rule matched',
            ]
        );

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE USER
    |--------------------------------------------------------------------------
    */

    public function create(User $user): bool
    {
        $isSystemAdmin = $this->isSystemAdmin($user);
        $isAdmin = $this->isAdmin($user);

        $allowed = $isSystemAdmin || $isAdmin;

        $this->logDecision(
            'create',
            $user,
            $allowed,
            [
                'is_system_admin' => $isSystemAdmin,
                'is_admin' => $isAdmin,

                'reason' => $allowed
                    ? 'SUPER_ADMIN or ADMIN'
                    : 'User is not SUPER_ADMIN or ADMIN',
            ]
        );

        return $allowed;
    }

    /*
    |--------------------------------------------------------------------------
    | ASSIGN ROLE TO NEW USER
    |--------------------------------------------------------------------------
    |
    | SUPER_ADMIN:
    |     ADMIN
    |     SUPERVISOR
    |     INSPECTOR
    |
    | ADMIN:
    |     SUPERVISOR
    |     INSPECTOR
    |
    | SUPER_ADMIN can never be assigned through this operation.
    |
    */

    public function assignRole(
        User $user,
        string $targetRole
    ): bool {
        $targetRole = strtoupper(trim($targetRole));

        /*
        |--------------------------------------------------------------------------
        | Gather role information
        |--------------------------------------------------------------------------
        */

        $spatieRoles = $user
            ->getRoleNames()
            ->values()
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Explicit API permission check
        |--------------------------------------------------------------------------
        */

        $permissionName = 'users.assign_role';

        $hasPermission = $this->hasApiPermission(
            $user,
            $permissionName
        );

        /*
        |--------------------------------------------------------------------------
        | Optional diagnostic
        |--------------------------------------------------------------------------
        |
        | Keep this separate from the actual authorization decision.
        |
        | `$user->can()` is intentionally NOT used as the source of truth.
        |
        */

        $hasPermissionViaCan = false;

        try {
            $hasPermissionViaCan = $user->can(
                $permissionName
            );
        } catch (\Throwable $e) {

            Log::debug(
                'USER POLICY: Gate permission diagnostic failed',
                [
                    'auth_user_id' => $user->id,
                    'permission' => $permissionName,
                    'error' => $e->getMessage(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Get all API permissions for diagnostics
        |--------------------------------------------------------------------------
        */

        $permissions = [];

        try {
            $permissions = $user
                ->getAllPermissions()
                ->filter(function ($permission) {
                    return $permission->guard_name === 'api';
                })
                ->pluck('name')
                ->sort()
                ->values()
                ->toArray();
        } catch (\Throwable $e) {

            Log::debug(
                'USER POLICY: Could not load API permission diagnostics',
                [
                    'auth_user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Role diagnostics
        |--------------------------------------------------------------------------
        */

        $isSystemAdmin = $this->isSystemAdmin($user);
        $isAdmin = $this->isAdmin($user);
        $isSupervisor = $this->isSupervisor($user);
        $isInspector = $this->isInspector($user);

        /*
        |--------------------------------------------------------------------------
        | Authorization diagnostics
        |--------------------------------------------------------------------------
        */

        Log::info(
            'USER POLICY: Role assignment authorization check',
            [
                'ability' => 'assignRole',

                'auth_user_id' => $user->id,
                'auth_email' => $user->email,

                /*
                |--------------------------------------------------------------------------
                | Database role column
                |--------------------------------------------------------------------------
                */

                'auth_role_column' => $user->role,

                /*
                |--------------------------------------------------------------------------
                | Spatie roles
                |--------------------------------------------------------------------------
                */

                'spatie_roles' => $spatieRoles,

                /*
                |--------------------------------------------------------------------------
                | Organizational scope
                |--------------------------------------------------------------------------
                */

                'auth_level' => $user->level,
                'city_id' => $user->city_id,
                'subcity_id' => $user->subcity_id,
                'wereda_id' => $user->wereda_id,

                /*
                |--------------------------------------------------------------------------
                | Target
                |--------------------------------------------------------------------------
                */

                'target_role' => $targetRole,

                /*
                |--------------------------------------------------------------------------
                | Role checks
                |--------------------------------------------------------------------------
                */

                'is_system_admin' => $isSystemAdmin,
                'is_admin' => $isAdmin,
                'is_supervisor' => $isSupervisor,
                'is_inspector' => $isInspector,

                /*
                |--------------------------------------------------------------------------
                | Permission checks
                |--------------------------------------------------------------------------
                */

                'permission_name' => $permissionName,

                /*
                | IMPORTANT:
                | This is the actual authorization check.
                */

                'has_permission_api' => $hasPermission,

                /*
                | Diagnostic only.
                */

                'has_permission_via_can' => $hasPermissionViaCan,

                /*
                |--------------------------------------------------------------------------
                | API permissions
                |--------------------------------------------------------------------------
                */

                'permission_count' => count($permissions),
                'api_permissions' => $permissions,

                /*
                |--------------------------------------------------------------------------
                | Guard diagnostics
                |--------------------------------------------------------------------------
                |
                | FIXED: `App\Models\User::getGuardNames()` does not exist.
                | Use the static Spatie helper instead, wrapped so it can
                | never break the actual authorization flow.
                |--------------------------------------------------------------------------
                */

                'auth_guard' => auth()->getDefaultDriver(),

                'user_guard_name' => $this->safeGuardNames($user),

                /*
                |--------------------------------------------------------------------------
                | Model diagnostics
                |--------------------------------------------------------------------------
                */

                'user_class' => get_class($user),
                'user_connection' => $user->getConnectionName(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | BASE PERMISSION CHECK
        |--------------------------------------------------------------------------
        */

        if (!$hasPermission) {

            Log::warning(
                'USER POLICY: Role assignment denied - API permission missing',
                [
                    ...$this->authContext($user),

                    'target_role' => $targetRole,

                    'permission' => $permissionName,

                    'has_permission_api' => false,

                    'has_permission_via_can' => $hasPermissionViaCan,

                    'permission_count' => count($permissions),
                    'api_permissions' => $permissions,

                    'is_system_admin' => $isSystemAdmin,
                    'is_admin' => $isAdmin,

                    'auth_guard' => auth()->getDefaultDriver(),

                    'reason' =>
                        'Authenticated user does not have users.assign_role on the api guard.',
                ]
            );

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN SAFETY
        |--------------------------------------------------------------------------
        |
        | SUPER_ADMIN must never be assignable as a target role.
        |
        */

        if ($targetRole === 'SUPER_ADMIN') {

            Log::warning(
                'USER POLICY: Role assignment denied - SUPER_ADMIN cannot be assigned',
                [
                    ...$this->authContext($user),

                    'target_role' => $targetRole,

                    'permission' => $permissionName,

                    'reason' =>
                        'SUPER_ADMIN is protected and cannot be assigned through user creation.',
                ]
            );

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | ROLE HIERARCHY CHECK
        |--------------------------------------------------------------------------
        */

        $hierarchyAllowed = $this->checkRoleHierarchy(
            $user,
            $targetRole
        );

        /*
        |--------------------------------------------------------------------------
        | Final decision
        |--------------------------------------------------------------------------
        */

        $allowed = $hierarchyAllowed;

        Log::info(
            'USER POLICY: Role assignment final decision',
            [
                ...$this->authContext($user),

                'target_role' => $targetRole,

                'permission' => $permissionName,

                'has_permission_api' => $hasPermission,

                'is_system_admin' => $isSystemAdmin,
                'is_admin' => $isAdmin,
                'is_supervisor' => $isSupervisor,
                'is_inspector' => $isInspector,

                'hierarchy_allowed' => $hierarchyAllowed,

                'final_allowed' => $allowed,

                'reason' => $allowed
                    ? 'API permission and role hierarchy checks passed.'
                    : 'API permission exists but role hierarchy denied assignment.',
            ]
        );

        return $allowed;
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE USER
    |--------------------------------------------------------------------------
    */

    public function update(
        User $user,
        User $model
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |--------------------------------------------------------------------------
        */

        if ($this->isSystemAdmin($user)) {

            $this->logDecision(
                'update',
                $user,
                true,
                [
                    'target_user_id' => $model->id,
                    'target_role' => $model->role,
                    'reason' => 'SUPER_ADMIN global access',
                ]
            );

            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent lower-level users from modifying SUPER_ADMIN
        |--------------------------------------------------------------------------
        */

        $targetIsSuperAdmin = $model->hasRole(
            'SUPER_ADMIN',
            'api'
        );

        if ($targetIsSuperAdmin) {

            $this->logDecision(
                'update',
                $user,
                false,
                [
                    'target_user_id' => $model->id,
                    'target_role' => $model->role,
                    'reason' => 'Target is SUPER_ADMIN',
                ]
            );

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | ADMIN → SAME CITY
        |--------------------------------------------------------------------------
        */

        $isAdmin = $this->isAdmin($user);
        $sameCity = $this->sameCity($user, $model);

        if ($isAdmin && $sameCity) {

            $this->logDecision(
                'update',
                $user,
                true,
                [
                    'target_user_id' => $model->id,
                    'target_role' => $model->role,
                    'reason' => 'ADMIN same city',
                    'same_city' => true,
                ]
            );

            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | SUPERVISOR → SAME SUBCITY
        |--------------------------------------------------------------------------
        */

        $isSupervisor = $this->isSupervisor($user);
        $sameSubCity = $this->sameSubCity($user, $model);

        if ($isSupervisor && $sameSubCity) {

            $this->logDecision(
                'update',
                $user,
                true,
                [
                    'target_user_id' => $model->id,
                    'target_role' => $model->role,
                    'reason' => 'SUPERVISOR same subcity',
                    'same_subcity' => true,
                ]
            );

            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | INSPECTOR → SAME WEREDA
        |--------------------------------------------------------------------------
        */

        $isInspector = $this->isInspector($user);
        $sameWereda = $this->sameWereda($user, $model);

        if ($isInspector && $sameWereda) {

            $this->logDecision(
                'update',
                $user,
                true,
                [
                    'target_user_id' => $model->id,
                    'target_role' => $model->role,
                    'reason' => 'INSPECTOR same wereda',
                    'same_wereda' => true,
                ]
            );

            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | DENIED
        |--------------------------------------------------------------------------
        */

        $this->logDecision(
            'update',
            $user,
            false,
            [
                'target_user_id' => $model->id,
                'target_role' => $model->role,

                'is_admin' => $isAdmin,
                'is_supervisor' => $isSupervisor,
                'is_inspector' => $isInspector,

                'same_city' => $sameCity,
                'same_subcity' => $sameSubCity,
                'same_wereda' => $sameWereda,

                'reason' => 'No applicable update rule matched',
            ]
        );

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE USER
    |--------------------------------------------------------------------------
    */

    public function delete(
        User $user,
        User $model
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN CHECK
        |--------------------------------------------------------------------------
        */

        $isSystemAdmin = $this->isSystemAdmin($user);

        if (!$isSystemAdmin) {

            $this->logDecision(
                'delete',
                $user,
                false,
                [
                    'target_user_id' => $model->id,
                    'target_role' => $model->role,
                    'reason' => 'Authenticated user is not SUPER_ADMIN',
                ]
            );

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent self deletion
        |--------------------------------------------------------------------------
        */

        if ($user->id === $model->id) {

            $this->logDecision(
                'delete',
                $user,
                false,
                [
                    'target_user_id' => $model->id,
                    'reason' => 'Cannot delete authenticated user',
                ]
            );

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | ALLOWED
        |--------------------------------------------------------------------------
        */

        $this->logDecision(
            'delete',
            $user,
            true,
            [
                'target_user_id' => $model->id,
                'target_role' => $model->role,
                'reason' => 'SUPER_ADMIN deleting another user',
            ]
        );

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PASSWORD
    |--------------------------------------------------------------------------
    */

    public function updatePassword(
        User $user,
        User $model
    ): bool {

        $isSystemAdmin = $this->isSystemAdmin($user);
        $isSelf = $user->id === $model->id;

        $allowed = $isSystemAdmin || $isSelf;

        $this->logDecision(
            'updatePassword',
            $user,
            $allowed,
            [
                'target_user_id' => $model->id,

                'is_system_admin' => $isSystemAdmin,

                'is_self' => $isSelf,

                'reason' => $allowed
                    ? 'SUPER_ADMIN or own account'
                    : 'Not authorized to update password',
            ]
        );

        return $allowed;
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE USER STATUS
    |--------------------------------------------------------------------------
    */

    public function updateStatus(
        User $user,
        User $model
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |--------------------------------------------------------------------------
        */

        if ($this->isSystemAdmin($user)) {

            $allowed = $user->id !== $model->id;

            $this->logDecision(
                'updateStatus',
                $user,
                $allowed,
                [
                    'target_user_id' => $model->id,

                    'target_role' => $model->role,

                    'reason' => $allowed
                        ? 'SUPER_ADMIN status management'
                        : 'Cannot change own status',
                ]
            );

            return $allowed;
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent ADMIN from modifying SUPER_ADMIN
        |--------------------------------------------------------------------------
        */

        if ($model->hasRole('SUPER_ADMIN', 'api')) {

            $this->logDecision(
                'updateStatus',
                $user,
                false,
                [
                    'target_user_id' => $model->id,
                    'target_role' => $model->role,
                    'reason' => 'Cannot modify SUPER_ADMIN status',
                ]
            );

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | ADMIN → SAME CITY
        |--------------------------------------------------------------------------
        */

        $isAdmin = $this->isAdmin($user);
        $sameCity = $this->sameCity($user, $model);

        if ($isAdmin && $sameCity) {

            $this->logDecision(
                'updateStatus',
                $user,
                true,
                [
                    'target_user_id' => $model->id,
                    'target_role' => $model->role,
                    'reason' => 'ADMIN same city',
                    'same_city' => true,
                ]
            );

            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | DENIED
        |--------------------------------------------------------------------------
        */

        $this->logDecision(
            'updateStatus',
            $user,
            false,
            [
                'target_user_id' => $model->id,
                'target_role' => $model->role,

                'is_admin' => $isAdmin,

                'same_city' => $sameCity,

                'reason' => 'No applicable status authorization rule matched',
            ]
        );

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE ROLE
    |--------------------------------------------------------------------------
    */

    public function updateRole(
        User $user,
        User $model,
        string $targetRole
    ): bool {

        $targetRole = strtoupper(trim($targetRole));

        /*
        |--------------------------------------------------------------------------
        | Base permission
        |--------------------------------------------------------------------------
        */

        $permissionName = 'users.assign_role';

        $hasPermission = $this->hasApiPermission(
            $user,
            $permissionName
        );

        Log::debug(
            'USER POLICY: Checking role update permission',
            [
                ...$this->authContext($user),

                'target_user_id' => $model->id,

                'current_target_role' => $model->role,

                'target_role' => $targetRole,

                'permission' => $permissionName,

                'permission_guard' => 'api',

                'has_permission' => $hasPermission,
            ]
        );

        if (!$hasPermission) {

            Log::warning(
                'USER POLICY: Role update denied - API permission missing',
                [
                    ...$this->authContext($user),

                    'target_user_id' => $model->id,

                    'target_role' => $targetRole,

                    'permission' => $permissionName,

                    'guard' => 'api',
                ]
            );

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent assigning SUPER_ADMIN
        |--------------------------------------------------------------------------
        */

        if ($targetRole === 'SUPER_ADMIN') {

            Log::warning(
                'USER POLICY: Role update denied - cannot assign SUPER_ADMIN',
                [
                    ...$this->authContext($user),

                    'target_user_id' => $model->id,

                    'current_target_role' => $model->role,

                    'target_role' => $targetRole,

                    'reason' =>
                        'SUPER_ADMIN cannot be assigned through role update.',
                ]
            );

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent self-role modification
        |--------------------------------------------------------------------------
        */

        if ($user->id === $model->id) {

            Log::warning(
                'USER POLICY: Role update denied - self modification',
                [
                    ...$this->authContext($user),

                    'target_user_id' => $model->id,

                    'target_role' => $targetRole,

                    'reason' =>
                        'Users cannot change their own role.',
                ]
            );

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Lower-level users cannot modify SUPER_ADMIN
        |--------------------------------------------------------------------------
        */

        $targetIsSuperAdmin = $model->hasRole(
            'SUPER_ADMIN',
            'api'
        );

        if (
            $targetIsSuperAdmin
            && !$this->isSystemAdmin($user)
        ) {

            Log::warning(
                'USER POLICY: Role update denied - target is SUPER_ADMIN',
                [
                    ...$this->authContext($user),

                    'target_user_id' => $model->id,

                    'current_target_role' => $model->role,

                    'target_role' => $targetRole,
                ]
            );

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Enforce hierarchy
        |--------------------------------------------------------------------------
        */

        $hierarchyAllowed = $this->checkRoleHierarchy(
            $user,
            $targetRole
        );

        Log::debug(
            'USER POLICY: Role update hierarchy evaluated',
            [
                ...$this->authContext($user),

                'target_user_id' => $model->id,

                'current_target_role' => $model->role,

                'target_role' => $targetRole,

                'hierarchy_allowed' => $hierarchyAllowed,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Final decision
        |--------------------------------------------------------------------------
        */

        $allowed = $hierarchyAllowed;

        $this->logDecision(
            'updateRole',
            $user,
            $allowed,
            [
                'target_user_id' => $model->id,

                'current_target_role' => $model->role,

                'target_role' => $targetRole,

                'permission' => $permissionName,

                'permission_guard' => 'api',

                'has_permission' => $hasPermission,

                'hierarchy_allowed' => $hierarchyAllowed,

                'reason' => $allowed
                    ? 'API permission and hierarchy checks passed'
                    : 'Role hierarchy denied target role',
            ]
        );

        return $allowed;
    }
}