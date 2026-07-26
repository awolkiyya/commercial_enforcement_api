<?php

namespace App\Modules\Inspection\Services;

use App\Models\Inspection;
use App\Models\PenaltyType;
use Illuminate\Support\Facades\DB;

class PenaltyEscalationService
{
    public function escalate(
        Inspection $inspection,
        PenaltyType $newPenaltyType,
        string $reason,
        string $newDueDate,
        $userId
    ) {
        return DB::transaction(function () use (
            $inspection,
            $newPenaltyType,
            $reason,
            $newDueDate,
            $userId
        ) {

            $currentPenalty = $inspection->penalty;

            if (!$currentPenalty) {
                throw new \Exception("No penalty exists to escalate.");
            }

            // =========================
            // UPDATE ONLY (NO CREATE)
            // =========================
            $currentPenalty->update([
                'penalty_type_id' => $newPenaltyType->id,
                'due_date' => $newDueDate,
                'status' => 'escalated', // ✅ VALID ENUM VALUE
                'notes' => trim($currentPenalty->notes . "\n\nESCALATION REASON: " . $reason),
            ]);

            return $currentPenalty->fresh();
        });
    }
}