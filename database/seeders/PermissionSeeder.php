<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'api';

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
            INSPECTIONS (FIELD OPERATIONS)
            =========================================================
            */
            'inspections.view',
            'inspections.create',        // Inspector creates field inspection
            'inspections.update',
            'inspections.submit',       // Final submission after field work
            'inspections.assign',

            /*
            =========================================================
            VIOLATIONS (CORE ENFORCEMENT LOGIC)
            =========================================================
            */
            'violations.view',
            'violations.create',        // Inspector detects illegal activity
            'violations.update',
            'violations.approve',       // Supervisor/Admin approval
            'violations.reject',
            'violations.close',

            /*
            =========================================================
            ENFORCEMENT ACTIONS (REAL WORLD POWER)
            =========================================================
            */
            'enforcement.view',
            'enforcement.issue_warning',
            'enforcement.apply_fine',
            'enforcement.order_closure',
            'enforcement.seizure',

            /*
            =========================================================
            CASE MANAGEMENT (FULL FLOW CONTROL)
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

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }
    }
}