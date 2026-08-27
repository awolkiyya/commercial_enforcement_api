<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Seed the application's API permissions.
     *
     * IMPORTANT:
     * This seeder defines WHAT actions exist in the system.
     * It does NOT define which roles may create/assign other roles.
     *
     * Role-assignment hierarchy must be enforced server-side
     * in the user management authorization layer.
     */
    public function run(): void
    {
        $guard = 'api';

        /*
        |--------------------------------------------------------------------------
        | Clear Spatie permission cache
        |--------------------------------------------------------------------------
        */
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Application Permissions
        |--------------------------------------------------------------------------
        */
        $permissions = [

            /*
            =========================================================
            USERS MANAGEMENT
            =========================================================
            */
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.assign_role',

            /*
            =========================================================
            BUSINESS REGISTRY
            =========================================================
            */
            'businesses.view',
            'businesses.create',
            'businesses.update',
            'businesses.delete',
            'businesses.verify',
            'businesses.suspend',

            /*
            =========================================================
            INSPECTIONS
            =========================================================
            */
            'inspections.view',
            'inspections.create',
            'inspections.update',
            'inspections.submit',
            'inspections.assign',

            /*
            =========================================================
            VIOLATIONS
            =========================================================
            */
            'violations.view',
            'violations.create',
            'violations.update',
            'violations.approve',
            'violations.reject',
            'violations.close',

            /*
            =========================================================
            ENFORCEMENT ACTIONS
            =========================================================
            */
            'enforcement.view',
            'enforcement.issue_warning',
            'enforcement.apply_fine',
            'enforcement.order_closure',
            'enforcement.seizure',

            /*
            =========================================================
            CASE MANAGEMENT
            =========================================================
            */
            'cases.view',
            'cases.create',
            'cases.update',
            'cases.assign',
            'cases.escalate',
            'cases.close',

            /*
            =========================================================
            REPORTING & ANALYTICS
            =========================================================
            */
            'reports.view',
            'reports.generate',
            'reports.export',
            'reports.city_level',
            'reports.subcity_level',
            'reports.woreda_level',

            /*
            =========================================================
            SYSTEM CONFIGURATION
            =========================================================
            */
            'roles.manage',
            'permissions.manage',
            'system.settings',
            'audit.logs.view',
        ];

        /*
        |--------------------------------------------------------------------------
        | Create permissions if they do not already exist
        |--------------------------------------------------------------------------
        */
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => $guard,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Clear cache after changes
        |--------------------------------------------------------------------------
        */
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}