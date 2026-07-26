<?php

namespace App\Modules\Inspection\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Inspection;
use App\Models\Violation;
use App\Models\Penalty;
use App\Models\InspectionParticipant;
use Andegna\DateTimeFactory;

class InspectionService
{
    public function create(array $data, string $userId): Inspection
    {
        return DB::transaction(function () use ($data, $userId) {

            Log::info('Inspection creation started', [
                'user_id' => $userId,
                'payload' => $data,
            ]);

            // =========================
            // ACTIVE INSPECTION CHECK
            // =========================
            $exists = Inspection::where('business_id', $data['business_id'])
                ->where('status', 'in_progress')
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'This business already has an active inspection in progress.'
                ]);
            }

            // =========================
            // ETHIOPIAN YEAR
            // =========================
            $year = DateTimeFactory::fromDateTime(now())->format('Y');

            // =========================
            // SEQUENCE
            // =========================
            $row = DB::table('inspection_sequences')
                ->where('year', $year)
                ->first();

            if (!$row) {
                DB::table('inspection_sequences')->insert([
                    'year' => $year,
                    'sequence' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $nextSequence = 1;
            } else {
                DB::table('inspection_sequences')
                    ->where('year', $year)
                    ->increment('sequence');

                $nextSequence = $row->sequence + 1;
            }

            $inspectionNumber = "INS-$year-" . str_pad($nextSequence, 6, '0', STR_PAD_LEFT);

            // =========================
            // MODE HANDLING
            // =========================
            $mode = $data['mode'] ?? 'personal';

            if ($mode === 'group' && empty($data['participants'])) {
                throw ValidationException::withMessages([
                    'participants' => 'At least one participant is required for group inspection.'
                ]);
            }

            // =========================
            // INSPECTION CREATE
            // =========================
            $inspection = Inspection::create([
                'id' => Str::uuid(),
                'inspection_number' => $inspectionNumber,
                'business_id' => $data['business_id'],
                'inspector_id' => $userId,
                'notes' => $data['notes'] ?? null,
                'started_at' => now(),
                'status' => 'in_progress',
            ]);

            // =========================
            // PARTICIPANTS (SMART)
            // =========================
            $participants = match ($mode) {

                'personal' => [$userId],

                'group' => collect($data['participants'] ?? [])
                    ->pluck('user_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),

                default => [$userId],
            };

            foreach ($participants as $participantId) {

                if (!Str::isUuid($participantId)) {
                    Log::warning('Invalid participant skipped', [
                        'user_id' => $participantId,
                    ]);
                    continue;
                }

                InspectionParticipant::create([
                    'id' => Str::uuid(),
                    'inspection_id' => $inspection->id,
                    'user_id' => $participantId,
                ]);
            }

            // =========================
            // VIOLATIONS
            // =========================
            foreach ($data['violations'] ?? [] as $v) {

                if (!Str::isUuid($v['violation_type_id'] ?? null)) {
                    Log::warning('Invalid violation_type_id skipped', $v);
                    continue;
                }

                Violation::create([
                    'id' => Str::uuid(),
                    'inspection_id' => $inspection->id,
                    'business_id' => $data['business_id'],
                    'violation_type_id' => $v['violation_type_id'],
                    'description' => $v['description'] ?? null,
                    'inspector_id' => $userId,
                ]);
            }

              // PENALTY (FIXED CRITICAL BUG HERE)
            // =========================
            $penalty = null;

            $penaltyTypeId = $data['penalty']['penalty_type_id'] ?? null;

            if ($penaltyTypeId) {

                // 🔥 CRITICAL FIX: convert numeric → UUID or reject
                if (!Str::isUuid($penaltyTypeId)) {

                    Log::error('INVALID penalty_type_id received (must be UUID)', [
                        'penalty_type_id' => $penaltyTypeId,
                    ]);

                    throw ValidationException::withMessages([
                        'penalty_type_id must be UUID (frontend is sending integer)'
                    ]);
                }

                $penalty = Penalty::create([
                    'id' => Str::uuid(),
                    'penalty_type_id' => $penaltyTypeId,
                    'due_date' => $data['penalty']['due_date'] ?? null,
                    'notes' => $data['penalty']['notes'] ?? null,
                    'issued_by' => $userId,
                ]);

                $inspection->update([
                    'penalty_id' => $penalty->id,
                ]);
            }

            Log::info('Inspection creation completed', [
                'inspection_id' => $inspection->id,
                'mode' => $mode,
            ]);

            return $inspection;
        });
    }
}