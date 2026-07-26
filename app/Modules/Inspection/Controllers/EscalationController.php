<?php

namespace App\Modules\Inspection\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Inspection;
use App\Models\PenaltyType;
use App\Modules\Inspection\Services\PenaltyEscalationService;

class EscalationController extends Controller
{
    public function escalate(Request $request, $id)
    {
        Log::info('ESCALATION_REQUEST_RECEIVED', [
            'inspection_id' => $id,
            'user_id' => auth()->id(),
            'payload' => $request->all(),
        ]);

        $validated = $request->validate([
            'penalty_type_id' => 'required|exists:penalty_types,id',
            'reason' => 'required|string|min:20',
            'new_due_date' => 'required|date|after_or_equal:today',
        ]);

        Log::info('ESCALATION_VALIDATED', [
            'inspection_id' => $id,
            'validated' => $validated,
        ]);

        $inspection = Inspection::with(['penalty.penaltyType'])
            ->findOrFail($id);

        $newPenaltyType = PenaltyType::findOrFail($validated['penalty_type_id']);

        $currentPenalty = $inspection->penalty?->penaltyType;

        /*
        |------------------------------------------------------
        | RANK VALIDATION (FIXED)
        |------------------------------------------------------
        | IMPORTANT: we use `category`, NOT `name`
        */
        if ($currentPenalty) {

            $rank = config('penalty.rank');

            $currentRank = $rank[$currentPenalty->category] ?? 0;
            $newRank = $rank[$newPenaltyType->category] ?? 0;

            Log::info('ESCALATION_RANK_CHECK', [
                'current_penalty_category' => $currentPenalty->category,
                'new_penalty_category' => $newPenaltyType->category,
                'current_rank' => $currentRank,
                'new_rank' => $newRank,
            ]);

            if ($newRank <= $currentRank) {

                Log::warning('ESCALATION_BLOCKED_INVALID_RANK', [
                    'inspection_id' => $id,
                    'current_rank' => $currentRank,
                    'new_rank' => $newRank,
                    'reason' => 'new penalty rank is not higher',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid escalation: new penalty must be higher severity than current penalty',
                ], 422);
            }
        }

        try {

            Log::info('ESCALATION_SERVICE_START', [
                'inspection_id' => $id,
                'user_id' => auth()->id(),
            ]);

            $result = app(PenaltyEscalationService::class)->escalate(
                inspection: $inspection,
                newPenaltyType: $newPenaltyType,
                reason: $validated['reason'],
                newDueDate: $validated['new_due_date'],
                userId: auth()->id()
            );

            Log::info('ESCALATION_SUCCESS', [
                'inspection_id' => $id,
                'new_penalty_id' => $result->id ?? null,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Penalty escalated successfully',
                'data' => $result,
            ]);

        } catch (\Throwable $e) {

            Log::error('ESCALATION_FAILED', [
                'inspection_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Escalation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}