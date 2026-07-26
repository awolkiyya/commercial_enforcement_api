<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('market_categories')->insert([
            [
                "id" => Str::uuid(),
                "name" => "Nyaataa fi Qonna / Food & Agriculture",
                "description" => "OR: Nyaata guyyaa guyyaa fi oomisha qonnaa kan akka midhaan, kuduraalee fi biqiltuu.\nEN: Daily food items and agricultural products such as grains, vegetables, and staples.",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "id" => Str::uuid(),
                "name" => "Meeshaalee Ijaarsaa / Construction Materials",
                "description" => "OR: Simintoo, sibiila, cirracha fi mukaa ijaarsaa keessatti fayyadaman.\nEN: Building materials like cement, iron, sand, and wood.",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "id" => Str::uuid(),
                "name" => "Boba’aa fi Humna / Fuel & Energy",
                "description" => "OR: Boba’aa, boba’aa konkolaataa fi gaasii manaa.\nEN: Fuel products including gasoline, diesel, and cooking gas.",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "id" => Str::uuid(),
                "name" => "Beeylada fi Oomisha Beeylada / Livestock & Animal Products",
                "description" => "OR: Beeylada lubbu-qabeeyyii fi oomisha isaanii kan akka foon, aannan fi hanqaaquu.\nEN: Live animals, meat, milk, eggs, and related products.",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "id" => Str::uuid(),
                "name" => "Meeshaalee Manaa / Household Goods",
                "description" => "OR: Meeshaalee manaa guyyaa guyyaa kan akka saamunaa, zayita fi soogidda.\nEN: Daily household items like soap, oil, salt, and consumer essentials.",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "id" => Str::uuid(),
                "name" => "Geejjiba / Transportation",
                "description" => "OR: Tajaajila geejjibaa fi baasii geejjibaa.\nEN: Transport-related costs and services.",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "id" => Str::uuid(),
                "name" => "Fayyaa fi Fayyaa Ummataa / Medical & Health",
                "description" => "OR: Meeshaalee fayyaa bu’uuraa fi tajaajila fayyaa.\nEN: Basic medical supplies and health-related products.",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "id" => Str::uuid(),
                "name" => "Kan Biroo / Other Goods",
                "description" => "OR: Meeshaalee biroo gara kutaa biraatti hin ramadamne.\nEN: Miscellaneous market items not classified elsewhere.",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ]);
    }
}