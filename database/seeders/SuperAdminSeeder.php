<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Clear Spatie permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /**
         * =========================================
         * CONFIG
         * =========================================
         */
        $guard = 'api';

        /**
         * =========================================
         * ENSURE ROLE EXISTS
         * =========================================
         */
        $role = Role::firstOrCreate([
            'name' => 'SUPER_ADMIN',
            'guard_name' => $guard,
        ]);

        /**
         * =========================================
         * ENSURE USER EXISTS
         * =========================================
         */
        $user = User::firstOrCreate(
            [
                'email' => 'admin@adama.local',
            ],
            [
                'id' => Str::uuid(),

                'name' => 'System Administrator',
                "level" =>"CITY",
                "role"=> "SUPER_ADMIN",

                'phone' => '0000000000',

                'password' => Hash::make('password123'),

                'is_active' => true,

                'email_verified_at' => now(),
                'last_login_at' => null,
            ]
        );

        /**
         * =========================================
         * ASSIGN ROLE (SAFE)
         * =========================================
         */
        $user->syncRoles([$role]);

        /**
         * =========================================
         * OPTIONAL: SUPER ADMIN FULL ACCESS
         * (ONLY IF PERMISSIONS EXIST)
         * =========================================
         */
        $permissions = Permission::where('guard_name', $guard)->get();

        if ($permissions->isNotEmpty()) {
            $role->syncPermissions($permissions);
        }

        /**
         * Refresh cache after changes
         */
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}