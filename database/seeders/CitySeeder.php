<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('cities')->insert([

            [
                'id' => Str::uuid(),

                'code' => 'ADAMA',

                'name' =>"ADAMA",

                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}