<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait ChecksHierarchy
{
    /*
    |--------------------------------------------------------------------------
    | ROLE CHECKS
    |--------------------------------------------------------------------------
    */

    protected function isSystemAdmin(User $user): bool
    {
        return $user->hasRole('SUPER_ADMIN');
    }

    protected function isAdmin(User $user): bool
    {
        return $user->hasRole('ADMIN');
    }

    protected function isSupervisor(User $user): bool
    {
        return $user->hasRole('SUPERVISOR');
    }

    protected function isInspector(User $user): bool
    {
        return $user->hasRole('INSPECTOR');
    }

    protected function canAssignRole(
        User $user,
        string $targetRole
    ): bool {
        $targetRole = strtoupper(trim($targetRole));
    
        $isSystemAdmin = $this->isSystemAdmin($user);
        $isAdmin = $this->isAdmin($user);
        $isSupervisor = $this->isSupervisor($user);
        $isInspector = $this->isInspector($user);
    
        /*
        |--------------------------------------------------------------------------
        | SUPER_ADMIN can NEVER be assigned normally
        |--------------------------------------------------------------------------
        */
    
        if ($targetRole === 'SUPER_ADMIN') {
    
            \Log::warning(
                'USER POLICY: Role hierarchy denied SUPER_ADMIN assignment',
                [
                    'auth_user_id' => $user->id,
                    'auth_email' => $user->email,
                    'auth_role_column' => $user->role,
                    'spatie_roles' => $user->getRoleNames()->values()->toArray(),
    
                    'target_role' => $targetRole,
    
                    'is_system_admin' => $isSystemAdmin,
                    'is_admin' => $isAdmin,
                    'is_supervisor' => $isSupervisor,
                    'is_inspector' => $isInspector,
    
                    'allowed' => false,
    
                    'reason' =>
                        'SUPER_ADMIN cannot be assigned through normal user management.',
                ]
            );
    
            return false;
        }
    
        /*
        |--------------------------------------------------------------------------
        | SUPER_ADMIN
        |--------------------------------------------------------------------------
        */
    
        if ($isSystemAdmin) {
    
            $allowed = in_array(
                $targetRole,
                [
                    'ADMIN',
                    'SUPERVISOR',
                    'INSPECTOR',
                ],
                true
            );
    
            \Log::debug(
                'USER POLICY: SUPER_ADMIN role hierarchy evaluated',
                [
                    'auth_user_id' => $user->id,
                    'target_role' => $targetRole,
                    'allowed' => $allowed,
                    'allowed_roles' => [
                        'ADMIN',
                        'SUPERVISOR',
                        'INSPECTOR',
                    ],
                ]
            );
    
            return $allowed;
        }
    
        /*
        |--------------------------------------------------------------------------
        | ADMIN
        |--------------------------------------------------------------------------
        */
    
        if ($isAdmin) {
    
            $allowed = in_array(
                $targetRole,
                [
                    'SUPERVISOR',
                    'INSPECTOR',
                ],
                true
            );
    
            \Log::debug(
                'USER POLICY: ADMIN role hierarchy evaluated',
                [
                    'auth_user_id' => $user->id,
                    'target_role' => $targetRole,
                    'allowed' => $allowed,
                    'allowed_roles' => [
                        'SUPERVISOR',
                        'INSPECTOR',
                    ],
                ]
            );
    
            return $allowed;
        }
    
        /*
        |--------------------------------------------------------------------------
        | SUPERVISOR
        |--------------------------------------------------------------------------
        */
    
        if ($isSupervisor) {
    
            \Log::debug(
                'USER POLICY: SUPERVISOR role hierarchy denied',
                [
                    'auth_user_id' => $user->id,
                    'target_role' => $targetRole,
                    'allowed' => false,
                    'reason' => 'SUPERVISOR cannot assign roles.',
                ]
            );
    
            return false;
        }
    
        /*
        |--------------------------------------------------------------------------
        | INSPECTOR
        |--------------------------------------------------------------------------
        */
    
        if ($isInspector) {
    
            \Log::debug(
                'USER POLICY: INSPECTOR role hierarchy denied',
                [
                    'auth_user_id' => $user->id,
                    'target_role' => $targetRole,
                    'allowed' => false,
                    'reason' => 'INSPECTOR cannot assign roles.',
                ]
            );
    
            return false;
        }
    
        /*
        |--------------------------------------------------------------------------
        | UNKNOWN ROLE → FAIL CLOSED
        |--------------------------------------------------------------------------
        */
    
        \Log::warning(
            'USER POLICY: Unknown role - hierarchy denied',
            [
                'auth_user_id' => $user->id,
                'auth_email' => $user->email,
                'auth_role_column' => $user->role,
                'spatie_roles' => $user->getRoleNames()->values()->toArray(),
    
                'target_role' => $targetRole,
    
                'allowed' => false,
    
                'reason' => 'Authenticated user has no recognized authorization role.',
            ]
        );
    
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | GOVERNANCE SCOPE MATCHING
    |--------------------------------------------------------------------------
    |
    | UUID-safe comparisons.
    |
    |--------------------------------------------------------------------------
    */

    protected function sameCity(
        User $user,
        Model $model
    ): bool {
        return !empty($user->city_id)
            && !empty($model->city_id)
            && (string) $user->city_id === (string) $model->city_id;
    }

    protected function sameSubCity(
        User $user,
        Model $model
    ): bool {
        return !empty($user->subcity_id)
            && !empty($model->subcity_id)
            && (string) $user->subcity_id === (string) $model->subcity_id;
    }

    protected function sameWereda(
        User $user,
        Model $model
    ): bool {
        return !empty($user->wereda_id)
            && !empty($model->wereda_id)
            && (string) $user->wereda_id === (string) $model->wereda_id;
    }

    /*
    |--------------------------------------------------------------------------
    | GOVERNANCE ACCESS
    |--------------------------------------------------------------------------
    |
    | Determines whether the authenticated user can access a model
    | within their administrative geographic scope.
    |
    |--------------------------------------------------------------------------
    */

    protected function hasAccessToModel(
        User $user,
        Model $model
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN → GLOBAL ACCESS
        |--------------------------------------------------------------------------
        */

        if ($this->isSystemAdmin($user)) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Missing level → DENY
        |--------------------------------------------------------------------------
        */

        if (empty($user->level)) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Scope-based authorization
        |--------------------------------------------------------------------------
        */

        return match (strtoupper((string) $user->level)) {

            'CITY' => $this->sameCity(
                $user,
                $model
            ),

            'SUBCITY' => $this->sameSubCity(
                $user,
                $model
            ),

            'WEREDA' => $this->sameWereda(
                $user,
                $model
            ),

            default => false,
        };
    }
}