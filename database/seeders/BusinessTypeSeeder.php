<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BusinessTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $data = [

            // =========================
            // FOOD & BEVERAGE (HIGH RISK)
            // =========================
            [
                'name' => 'Mana Nyaataa (Restaurant)',
                'code' => 'restaurant',
                'description' => 'Restaurants, cafes, and food service establishments.',
                'category' => 'Nyaataa fi Dhugaatii',
                'priority_level' => 4,
                'is_movable' => false,
                'requires_permanent_address' => true,
                'requires_inspection' => true,
                'inspection_frequency_months' => 3,
            ],
            [
                'name' => 'Buna fi Shaayii (Coffee & Tea House)',
                'code' => 'coffee_shop',
                'description' => 'Coffee houses and tea serving businesses.',
                'category' => 'Nyaataa fi Dhugaatii',
                'priority_level' => 3,
                'is_movable' => false,
                'requires_permanent_address' => true,
                'requires_inspection' => true,
                'inspection_frequency_months' => 6,
            ],

            // =========================
            // HEALTH SECTOR (VERY HIGH RISK)
            // =========================
            [
                'name' => 'Mana Qorichaa (Pharmacy)',
                'code' => 'pharmacy',
                'description' => 'Licensed drug retail and pharmacy services.',
                'category' => 'Fayyaa',
                'priority_level' => 5,
                'is_movable' => false,
                'requires_permanent_address' => true,
                'requires_inspection' => true,
                'inspection_frequency_months' => 3,
            ],
            [
                'name' => 'Kilinikaa (Clinic)',
                'code' => 'clinic',
                'description' => 'Private clinics and outpatient medical centers.',
                'category' => 'Fayyaa',
                'priority_level' => 5,
                'is_movable' => false,
                'requires_permanent_address' => true,
                'requires_inspection' => true,
                'inspection_frequency_months' => 3,
            ],

            // =========================
            // HOSPITALITY & TOURISM
            // =========================
            [
                'name' => 'Hoteela',
                'code' => 'hotel',
                'description' => 'Hotels, lodges, guest houses.',
                'category' => 'Turizimii',
                'priority_level' => 3,
                'is_movable' => false,
                'requires_permanent_address' => true,
                'requires_inspection' => true,
                'inspection_frequency_months' => 6,
            ],
            [
                'name' => 'Mana Irraa Buufata Turizimii',
                'code' => 'tourism_service',
                'description' => 'Tour operators, travel agencies.',
                'category' => 'Turizimii',
                'priority_level' => 2,
                'is_movable' => true,
                'requires_permanent_address' => true,
                'requires_inspection' => true,
                'inspection_frequency_months' => 12,
            ],

            // =========================
            // TRADE & COMMERCE
            // =========================
            [
                'name' => 'Daldala Waliigalaa (Retail/Wholesale)',
                'code' => 'general_trade',
                'description' => 'Retail shops and wholesale trading.',
                'category' => 'Daldala',
                'priority_level' => 2,
                'is_movable' => true,
                'requires_permanent_address' => false,
                'requires_inspection' => true,
                'inspection_frequency_months' => 12,
            ],
            [
                'name' => 'Suuqii (Market Stall)',
                'code' => 'market_stall',
                'description' => 'Open market vendors and stalls.',
                'category' => 'Daldala',
                'priority_level' => 1,
                'is_movable' => true,
                'requires_permanent_address' => false,
                'requires_inspection' => false,
                'inspection_frequency_months' => 24,
            ],

            // =========================
            // MANUFACTURING & INDUSTRY
            // =========================
            [
                'name' => 'Oomisha (Manufacturing)',
                'code' => 'manufacturing',
                'description' => 'Factories and production industries.',
                'category' => 'Oomisha',
                'priority_level' => 4,
                'is_movable' => false,
                'requires_permanent_address' => true,
                'requires_inspection' => true,
                'inspection_frequency_months' => 6,
            ],
            [
                'name' => 'Qurxummi & Albuuda (Workshop)',
                'code' => 'workshop',
                'description' => 'Metal, wood, mechanical workshops.',
                'category' => 'Oomisha',
                'priority_level' => 3,
                'is_movable' => false,
                'requires_permanent_address' => true,
                'requires_inspection' => true,
                'inspection_frequency_months' => 12,
            ],

            // =========================
            // TRANSPORT & LOGISTICS
            // =========================
            [
                'name' => 'Geejjibaa (Transport)',
                'code' => 'transport',
                'description' => 'Taxi, bus, freight, logistics services.',
                'category' => 'Geejjibaa',
                'priority_level' => 3,
                'is_movable' => true,
                'requires_permanent_address' => false,
                'requires_inspection' => true,
                'inspection_frequency_months' => 12,
            ],

            // =========================
            // EDUCATION
            // =========================
            [
                'name' => 'Barnoota (School)',
                'code' => 'school',
                'description' => 'Private schools and educational institutions.',
                'category' => 'Barnootaa',
                'priority_level' => 3,
                'is_movable' => false,
                'requires_permanent_address' => true,
                'requires_inspection' => true,
                'inspection_frequency_months' => 12,
            ],

            // =========================
            // SERVICES
            // =========================
            [
                'name' => 'Tajaajila (Service Business)',
                'code' => 'service_business',
                'description' => 'General service providers (salon, repair, etc).',
                'category' => 'Tajaajila',
                'priority_level' => 2,
                'is_movable' => true,
                'requires_permanent_address' => false,
                'requires_inspection' => false,
                'inspection_frequency_months' => 12,
            ],
        ];

        foreach ($data as $item) {
            DB::table('business_types')->insert([
                'id' => Str::uuid(),
                'name' => $item['name'],
                'code' => $item['code'],
                'description' => $item['description'],
                'category' => $item['category'],
                'priority_level' => $item['priority_level'],
                'is_movable' => $item['is_movable'],
                'requires_permanent_address' => $item['requires_permanent_address'],
                'requires_inspection' => $item['requires_inspection'],
                'inspection_frequency_months' => $item['inspection_frequency_months'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}