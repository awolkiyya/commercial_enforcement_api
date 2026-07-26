<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([

            // =========================
            // 1. CORE AUTH SYSTEM
            // =========================
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,


            // =========================
            // 2. GEO SYSTEM (DEPENDENCY FOR EVERYTHING)
            // =========================
            CitySeeder::class,
            SubCitySeeder::class,
            WeredaSeeder::class,

            // =========================
            // 3. USERS (SUPER ADMIN FIRST)
            // =========================
            SuperAdminSeeder::class,
            InspectorSeeder::class,

            // =========================
            // 4. BUSINESS REFERENCE DATA
            // =========================
            BusinessTypeSeeder::class,
            // =========================
            // 5. ENFORCEMENT SYSTEM DATA
            // =========================
            ViolationTypeSeeder::class,
            EnforcementActionTypeSeeder::class,

            // 6. MARKET CATEGORY
            MarketCategoriesSeeder::class
        ]);
    }
}