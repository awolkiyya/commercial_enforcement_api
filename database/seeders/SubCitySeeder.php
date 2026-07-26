<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class SubCitySeeder extends Seeder
{
    public function run(): void
    {
        $adama = DB::table('cities')
            ->where('code', 'ADAMA')
            ->first();

        if (!$adama) {
            throw new \RuntimeException("City with code ADAMA not found.");
        }

        $subcities = [
            [
                'name' => 'Kutaa Magaalaa Abbaa Gadaa',
            ],
            [
                'name' => 'Kutaa Magaalaa Boolee',
            ],
            [
                'name' => 'Kutaa Magaalaa Daabee',
            ],
            [
                'name' => 'Kutaa Magaalaa Bokkuu Shanan',
            ],
            [
                'name' => 'Kutaa Magaalaa Luugoo',
            ],
            [
                'name' => 'Kutaa Magaalaa Dambalaa',
            ],
        ];

        foreach ($subcities as $sub) {
            DB::table('subcities')->insert([
                'id' => Str::uuid(),
                'city_id' => $adama->id,
                'name' => $sub['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}