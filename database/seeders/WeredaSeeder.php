<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WeredaSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ FIX: correct table name (IMPORTANT)
        $subcities = DB::table('subcities')->get()->keyBy('name');

        $map = [
            'Kutaa Magaalaa Abbaa Gadaa' => [
                'Aanaa Buttaa',
                'Aanaa Badhaatuu',
                'Aanaa Odaa',
                'Aanaa Dagaagaa',
            ],

            'Kutaa Magaalaa Boolee' => [
                'Aanaa Dhaddacha Araaraa',
                'Aanaa Dhagaa Adii',
                'Aanaa Gooroo',
            ],

            'Kutaa Magaalaa Daabee' => [
                'Aanaa Caffee',
                'Aanaa Hangaatuu',
                'Aanaa Daabee Dongorree',
            ],

            'Kutaa Magaalaa Bokkuu Shanan' => [
                'Aanaa Torban Oboo',
                'Aanaa Aroorettii',
                'Aanaa Awaash Malkaa Sa’aa',
            ],

            'Kutaa Magaalaa Luugoo' => [
                'Aanaa Barreechaa',
                'Aanaa Migiraa',
                'Aanaa Dirree Nagaa',
            ],

            'Kutaa Magaalaa Dambalaa' => [
                'Aanaa Irreechaa',
                'Aanaa Malkaa Adaamaa',
                'Aanaa Wanjii',
            ],
        ];

        foreach ($map as $subcityName => $weredas) {

            $subcity = $subcities->get($subcityName);

            // ❗ STRICT MODE: fail fast instead of silent skip
            if (!$subcity) {
                throw new \Exception("Subcity not found: {$subcityName}");
            }

            foreach ($weredas as $name) {
                DB::table('weredas')->insert([
                    'id' => (string) Str::uuid(),
                    'subcity_id' => $subcity->id, // ⚠️ ensure correct column name
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}