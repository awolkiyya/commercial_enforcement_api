<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ViolationTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $violations = [

            // =========================
            // 1. LICENSING ISSUES
            // =========================
            [
                'id' => Str::uuid(),
                'name' => 'Hayyama Malee (No License)',
                'description' => 'Daldalli hayyama mootummaa malee hojii gaggeessa jiru.',
                'severity_level' => 'critical',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Haaromsuu Dhabuu (No License Renewal)',
                'description' => 'Hayyama daldalaa yeroo isaa keessatti haaromsuu dhabuu.',
                'severity_level' => 'high',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Hayyama Sobaa (Fake License)',
                'description' => 'Hayyama sobaa yookaan seeraan alaa fayyadamuu.',
                'severity_level' => 'critical',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Hayyama Harka Biraa Fayyadamuu (Misuse of License)',
                'description' => 'Hayyama nama biraa yookaan daldala biraa itti fayyadamuu.',
                'severity_level' => 'high',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =========================
            // 2. LOCATION / ZONING ISSUES
            // =========================
            [
                'id' => Str::uuid(),
                'name' => 'Dameen Ala (Out of Designated Zone)',
                'description' => 'Daldalli bakka hayyamame alatti gaggeeffamu.',
                'severity_level' => 'high',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Teessoo Ala (Invalid Address)',
                'description' => 'Daldalli teessoo sirrii hin qabne yookaan galmee mootummaa keessaa hin jirre.',
                'severity_level' => 'medium',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =========================
            // 3. STREET / PUBLIC SPACE
            // =========================
            [
                'id' => Str::uuid(),
                'name' => 'Daandii Irratti Daldaluu (Street Trading)',
                'description' => 'Daandii irratti seeraan alaa daldala gaggeessuu.',
                'severity_level' => 'medium',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Baranda Irratti Daldaluu (Sidewalk Trading)',
                'description' => 'Baranda irratti daldala gaggeessuu kan sochii uummataa gufachiisu.',
                'severity_level' => 'medium',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =========================
            // 4. BUSINESS STRUCTURE
            // =========================
            [
                'id' => Str::uuid(),
                'name' => 'Walitti Makuu (Unregistered Business Change)',
                'description' => 'Daldala mootummaa malee walitti makuu yookaan jijjiiruu.',
                'severity_level' => 'high',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =========================
            // 5. TAX & FINANCIAL COMPLIANCE
            // =========================
            [
                'id' => Str::uuid(),
                'name' => 'Galmee Gibiraa Dhabuu (No Tax Registration)',
                'description' => 'Daldalli galmee gibiraa mootummaa keessatti hin galmoofne.',
                'severity_level' => 'high',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Gibira Baasuu Dhabuu (Tax Non-Compliance)',
                'description' => 'Gibira mootummaa yeroo fi haala barbaachisuun hin kaffalamin.',
                'severity_level' => 'critical',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =========================
            // 6. HEALTH & SAFETY
            // =========================
            [
                'id' => Str::uuid(),
                'name' => 'Nageenya Dhabuu (Unsafe Conditions)',
                'description' => 'Haalli hojii balaa namaaf yookaan qabeenyaaf uumu.',
                'severity_level' => 'high',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Qulqullina Hin Eegamne (Poor Hygiene)',
                'description' => 'Haala qulqullinaa hojii irratti sadarkaa hin taane.',
                'severity_level' => 'medium',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =========================
            // 7. OPERATIONAL VIOLATIONS
            // =========================
            [
                'id' => Str::uuid(),
                'name' => 'Qorannoo Danquu (Obstruction of Inspection)',
                'description' => 'Qorannoo mootummaa gufachiisuu yookaan dhoorkuu.',
                'severity_level' => 'high',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Ajaja Qorannoo Diduu (Refusal of Inspection)',
                'description' => 'Ajaja mootummaa hojii qorannoo diduu.',
                'severity_level' => 'high',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =========================
            // 8. BUSINESS ETHICS / MARKET BEHAVIOR
            // =========================
            [
                'id' => Str::uuid(),
                'name' => 'Gatii Sobaa (False Pricing)',
                'description' => 'Gatii sobaa uummata gowwoomsuuf fayyadamuu.',
                'severity_level' => 'medium',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Uummata Gowwoomsuu (Misleading Practice)',
                'description' => 'Odeeffannoo sobaa kennuun daldala gaggeessuu.',
                'severity_level' => 'high',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =========================
            // 9. ADMIN / LEGAL / CONTROL
            // =========================
            [
                'id' => Str::uuid(),
                'name' => 'Galmee Sobaa Kennuu (False Information)',
                'description' => 'Odeeffannoo sobaa mootummaa irratti galmeessuu.',
                'severity_level' => 'critical',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =========================
            // 10. GENERAL RISK
            // =========================
            [
                'id' => Str::uuid(),
                'name' => 'Balaa Hamaa (Serious Public Risk)',
                'description' => 'Hojii balaa cimaa uummataaf yookaan qabeenyaaf uumu.',
                'severity_level' => 'critical',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('violation_types')->insert($violations);
    }
}