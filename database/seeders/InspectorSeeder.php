<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\DB;

class InspectorSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * =========================================
         * CLEAR CACHE
         * =========================================
         */
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'api';

        /**
         * =========================================
         * ROLE
         * =========================================
         */
        $role = Role::firstOrCreate([
            'name' => 'INSPECTOR',
            'guard_name' => $guard,
        ]);

        /**
         * =========================================
         * PERMISSIONS
         * =========================================
         */
        $permissions = [
            'business.create',
            'business.view',
            'violation.create',
            'violation.view',
            'inspection.perform',
            'report.submit',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => $guard,
            ]);
        }

        $role->syncPermissions($permissions);

        /**
         * =========================================
         * GET GEO BASE (SAFE CHECK)
         * =========================================
         */
        $wereda = DB::table('weredas')->first();

        if (!$wereda) {
            throw new \Exception("Seeder Error: No wereda found. Please run WeredaSeeder first.");
        }

        /**
         * =========================================
         * INSPECTORS DATA
         * =========================================
         */
        $inspectors = [
            [
                'name' => 'Abebe Kebede',
                'email' => 'abebe@inspector.com',
                'phone' => '0911111111',
            ],
            [
                'name' => 'Mulu Tesfaye',
                'email' => 'mulu@inspector.com',
                'phone' => '0922222222',
            ],
        ];

        /**
         * =========================================
         * CREATE USERS + ASSIGNMENTS
         * =========================================
         */
        foreach ($inspectors as $data) {

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'id' => Str::uuid(),
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make('password123'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'role' => 'INSPECTOR',

                    "level" =>"WEREDA",


                    'city_id' => $wereda->city_id ?? null,
                    'subcity_id' => $wereda->subcity_id ?? null,
                    'wereda_id' => $wereda->id,

                ]
            );

            /**
             * ASSIGN ROLE
             */
            $user->syncRoles([$role]);

            /**
             * =========================================
             * GEO ASSIGNMENT (FIXED FIELD NAME)
             * =========================================
             */
            // DB::table('user_assignments')->updateOrInsert(
            //     ['user_id' => $user->id],

            //     [
            //         'id' => Str::uuid(),
            //         'user_id' => $user->id,
            //         'role' => 'INSPECTOR',

            //         "level" =>"CITY",


            //         'city_id' => $wereda->city_id ?? null,
            //         'sub_city_id' => $wereda->sub_city_id ?? null,
            //         'wereda_id' => $wereda->id,

            //         // FIXED: must match migration
            //         'is_active' => true,
            //         'is_primary' => true,

            //         'created_at' => now(),
            //         'updated_at' => now(),
            //     ]
            // );
        }

        /**
         * =========================================
         * REFRESH CACHE
         * =========================================
         */
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}