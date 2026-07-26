<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /*
        =========================================================
        SUPER ADMIN → EVERYTHING
        =========================================================
        */
        $superAdmin = Role::findByName('SUPER_ADMIN');
        $superAdmin->givePermissionTo(\Spatie\Permission\Models\Permission::all());

        /*
        =========================================================
        ADMIN → MANAGEMENT + REPORTING
        =========================================================
        */
        $admin = Role::findByName('ADMIN');
        $admin->givePermissionTo([
            'users.view',
            'users.create',
            'users.update',

            'businesses.view',
            'businesses.verify',

            'cases.view',
            'cases.assign',

            'inspections.view',

            'violations.view',
            'violations.approve',
            'violations.reject',

            'reports.view',
            'reports.generate',
        ]);

        /*
        =========================================================
        INSPECTOR → FIELD OPERATIONS ONLY
        =========================================================
        */
        $inspector = Role::findByName('INSPECTOR');
        $inspector->givePermissionTo([
            'inspections.create',
            'inspections.view',
            'inspections.submit',

            'violations.create',

            'businesses.view',

            'cases.view',
        ]);

        /*
        =========================================================
        SUPERVISOR → CONTROL / APPROVAL LAYER
        =========================================================
        */
        $supervisor = Role::findByName('SUPERVISOR');
        $supervisor->givePermissionTo([
            'inspections.view',

            'violations.view',
            'violations.approve',
            'violations.reject',

            'enforcement.issue_warning',
            'enforcement.apply_fine',
            'enforcement.order_closure',

            'cases.view',
            'cases.escalate',

            'reports.view',
        ]);
    }
}