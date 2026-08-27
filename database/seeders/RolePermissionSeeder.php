<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the exact permission set for every application role.
     *
     * IMPORTANT:
     * This seeder defines WHAT each role can do.
     *
     * It does NOT determine which role an administrator may create.
     * That role hierarchy must be enforced server-side when creating
     * or updating users.
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
        | Load roles
        |--------------------------------------------------------------------------
        */
        $superAdmin = Role::findByName('SUPER_ADMIN', $guard);
        $admin      = Role::findByName('ADMIN', $guard);
        $supervisor = Role::findByName('SUPERVISOR', $guard);
        $inspector  = Role::findByName('INSPECTOR', $guard);

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |
        | Full system access.
        |--------------------------------------------------------------------------
        */
        $superAdmin->syncPermissions(
            Permission::where('guard_name', $guard)->get()
        );

        /*
        |--------------------------------------------------------------------------
        | ADMIN
        |
        | Administrative management and operational oversight.
        |
        | IMPORTANT:
        | ADMIN has users.assign_role because ADMIN may create users,
        | but the backend MUST restrict ADMIN from assigning SUPER_ADMIN.
        |--------------------------------------------------------------------------
        */
        $admin->syncPermissions([
            /*
            User Management
            */
            'users.view',
            'users.create',
            'users.update',
            'users.assign_role',

            /*
            Business Registry
            */
            'businesses.view',
            'businesses.verify',

            /*
            Cases
            */
            'cases.view',
            'cases.assign',

            /*
            Inspections
            */
            'inspections.view',

            /*
            Violations
            */
            'violations.view',
            'violations.approve',
            'violations.reject',

            /*
            Reporting
            */
            'reports.view',
            'reports.generate',
        ]);

        /*
        |--------------------------------------------------------------------------
        | SUPERVISOR
        |
        | Operational control and approval layer.
        |--------------------------------------------------------------------------
        */
        $supervisor->syncPermissions([
            /*
            Inspections
            */
            'inspections.view',

            /*
            Violations
            */
            'violations.view',
            'violations.approve',
            'violations.reject',

            /*
            Enforcement
            */
            'enforcement.view',
            'enforcement.issue_warning',
            'enforcement.apply_fine',
            'enforcement.order_closure',

            /*
            Cases
            */
            'cases.view',
            'cases.escalate',

            /*
            Reporting
            */
            'reports.view',
        ]);

        /*
        |--------------------------------------------------------------------------
        | INSPECTOR
        |
        | Field operations only.
        |--------------------------------------------------------------------------
        */
        $inspector->syncPermissions([
            /*
            Inspections
            */
            'inspections.view',
            'inspections.create',
            'inspections.submit',

            /*
            Violations
            */
            'violations.create',

            /*
            Business Registry
            */
            'businesses.view',

            /*
            Cases
            */
            'cases.view',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Clear Spatie permission cache
        |--------------------------------------------------------------------------
        */
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}