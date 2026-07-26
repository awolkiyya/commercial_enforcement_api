<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnforcementActionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $actions = [

            [
                'id' => Str::uuid(),
                'name' => 'Of Eeggannoo (Warning)',
                'category' => 'warning',
                'description' => 'Akeekkachiisa hojii irratti kennamu sababa seera xiqqaa cabseef.',
                'status' => true,
                'requires_due_date' => false,
                'is_final_action' => false,
                'allows_escalation' => true,
                'stops_inspection_flow' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => Str::uuid(),
                'name' => 'Qorannoo Addaa (Special Inspection)',
                'category' => 'monitoring',
                'description' => 'Qorannoo dabalataa hojii irratti gaggeeffamu sababa rakkoo argameef.',
                'status' => true,
                'requires_due_date' => true,
                'is_final_action' => false,
                'allows_escalation' => true,
                'stops_inspection_flow' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => Str::uuid(),
                'name' => 'Sirreeffama Dirqamaa (Corrective Action)',
                'category' => 'corrective',
                'description' => 'Hojii keessatti dogoggora jiru sirreessuuf dirqama kennamu.',
                'status' => true,
                'requires_due_date' => true,
                'is_final_action' => false,
                'allows_escalation' => true,
                'stops_inspection_flow' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => Str::uuid(),
                'name' => 'Saamsuu (Sealing)',
                'category' => 'restriction',
                'description' => 'Iddoo daldalaa yeroo murtaa’eef cufuu.',
                'status' => true,
                'requires_due_date' => true,
                'is_final_action' => false,
                'allows_escalation' => true,
                'stops_inspection_flow' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => Str::uuid(),
                'name' => 'Hayyama Dhorkuu (License Suspension)',
                'category' => 'suspension',
                'description' => 'Hayyama hojii yeroo murtaa’eef dhorkuu.',
                'status' => true,
                'requires_due_date' => true,
                'is_final_action' => true,
                'allows_escalation' => false,
                'stops_inspection_flow' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => Str::uuid(),
                'name' => 'Adabbii Maallaqaa (Fine)',
                'category' => 'fine',
                'description' => 'Adabbii maallaqaa yeroo seerri cabu.',
                'status' => true,
                'requires_due_date' => false,
                'is_final_action' => false,
                'allows_escalation' => true,
                'stops_inspection_flow' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => Str::uuid(),
                'name' => 'Himannaa Mana Murtii (Court Referral)',
                'category' => 'legal',
                'description' => 'Dhimma seera cabsaa gara mana murtii itti dabarsuu.',
                'status' => true,
                'requires_due_date' => false,
                'is_final_action' => true,
                'allows_escalation' => false,
                'stops_inspection_flow' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => Str::uuid(),
                'name' => 'Cufamuu Guutuu (Full Closure)',
                'category' => 'closure',
                'description' => 'Daldalli guutummaatti akka cufamu murtii kennamu.',
                'status' => true,
                'requires_due_date' => false,
                'is_final_action' => true,
                'allows_escalation' => false,
                'stops_inspection_flow' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('penalty_types')->insert($actions);
    }
}