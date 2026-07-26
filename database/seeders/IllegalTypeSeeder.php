<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IllegalTypeSeeder extends Seeder
{
    public function run(): void
    {
        $items = [

            [
                'code' => 'NO_LICENSE',
                'severity' => 'high',
                'en' => 'No License',
            ],

            [
                'code' => 'NO_TIN',
                'severity' => 'medium',
                'en' => 'No TIN',
            ],

            [
                'code' => 'EXPIRED_LICENSE',
                'severity' => 'medium',
                'en' => 'Expired License',
            ],

            [
                'code' => 'ILLEGAL_STREET_SELLING',
                'severity' => 'high',
                'en' => 'Illegal Street Selling',
            ],

            [
                'code' => 'HEALTH_VIOLATION',
                'severity' => 'critical',
                'en' => 'Health Violation',
            ],

            [
                'code' => 'FAKE_PRODUCTS',
                'severity' => 'critical',
                'en' => 'Fake Products',
            ],

        ];

        foreach ($items as $item) {

            DB::table('illegal_types')->insert([
                'id' => Str::uuid(),

                'name' => json_encode([
                    'en' => $item['en'],
                    'am' => $item['en'],
                    'or' => $item['en'],
                ]),

                'description' => json_encode([
                    'en' => $item['en'] . ' violation type',
                    'am' => $item['en'] . ' የሕግ ጥሰት',
                    'or' => $item['en'] . ' jechuun seera cabsuu',
                ]),

                'severity_level' => $item['severity'],

                'status' => true,

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}