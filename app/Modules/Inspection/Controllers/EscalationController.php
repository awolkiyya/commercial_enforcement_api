<?php

namespace App\Modules\Inspection\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Inspection;
use App\Models\PenaltyType;
use App\Modules\Inspection\Services\PenaltyEscalationService;
use App\Support\ApiResponse;

class EscalationController extends Controller
{
    /**
     * ESCALATE INSPECTION PENALTY
     *
     * SECURITY:
     * - Requires authenticated user.
     * - Requires policy authorization.
     * - Requires valid penalty escalation hierarchy.
     * - Business logic remains inside the service.
     */
    public function escalate(
        Request $request,
        string $id
    ) {
        try {
            $user = auth()->user();

            if (!$user) {
                return ApiResponse::error(
                    'Authentication required.',
                    [],
                    401
                );
            }

            Log::info('ESCALATION_REQUEST_RECEIVED', [
                'inspection_id' => $id,
                'user_id'       => $user->id,
                'user_roles'    => $user->getRoleNames(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | LOAD INSPECTION
            |--------------------------------------------------------------------------
            */

            $inspection = Inspection::with([
                'penalty.penaltyType',
            ])->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | SERVER-SIDE AUTHORIZATION
            |--------------------------------------------------------------------------
            |
            | This is the critical protection.
            |
            | The frontend must NOT be trusted to determine whether
            | escalation is allowed.
            |
            |--------------------------------------------------------------------------
            */

            $this->authorize(
                'escalate',
                $inspection
            );

            /*
            |--------------------------------------------------------------------------
            | VALIDATION
            |--------------------------------------------------------------------------
            */

            $validated = $request->validate([
                'penalty_type_id' => [
                    'required',
                    'exists:penalty_types,id',
                ],

                'reason' => [
                    'required',
                    'string',
                    'min:20',
                    'max:2000',
                ],

                'new_due_date' => [
                    'required',
                    'date',
                    'after_or_equal:today',
                ],
            ]);

            Log::info('ESCALATION_VALIDATED', [
                'inspection_id' => $inspection->id,
                'user_id'       => $user->id,
                'validated'     => $validated,
            ]);

            /*
            |--------------------------------------------------------------------------
            | LOAD NEW PENALTY TYPE
            |--------------------------------------------------------------------------
            */

            $newPenaltyType = PenaltyType::findOrFail(
                $validated['penalty_type_id']
            );

            $currentPenalty = $inspection
                ->penalty
                ?->penaltyType;

            /*
            |--------------------------------------------------------------------------
            | PENALTY ESCALATION RANK
            |--------------------------------------------------------------------------
            */

            if ($currentPenalty) {

                $rank = config('penalty.rank', []);

                $currentCategory = strtoupper(
                    trim((string) $currentPenalty->category)
                );

                $newCategory = strtoupper(
                    trim((string) $newPenaltyType->category)
                );

                $currentRank = $rank[$currentCategory] ?? 0;
                $newRank     = $rank[$newCategory] ?? 0;

                Log::info('ESCALATION_RANK_CHECK', [
                    'inspection_id'           => $inspection->id,
                    'current_penalty_category' => $currentCategory,
                    'new_penalty_category'     => $newCategory,
                    'current_rank'             => $currentRank,
                    'new_rank'                 => $newRank,
                ]);

                /*
                |--------------------------------------------------------------------------
                | FAIL CLOSED
                |--------------------------------------------------------------------------
                |
                | Unknown categories should NOT be treated as valid
                | escalation levels.
                |
                |--------------------------------------------------------------------------
                */

                if ($currentRank <= 0 || $newRank <= 0) {

                    Log::warning(
                        'ESCALATION_BLOCKED_UNKNOWN_RANK',
                        [
                            'inspection_id' => $inspection->id,
                            'current_category' => $currentCategory,
                            'new_category' => $newCategory,
                        ]
                    );

                    return ApiResponse::error(
                        'Invalid penalty escalation level.',
                        [],
                        422
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | NEW PENALTY MUST BE STRICTLY HIGHER
                |--------------------------------------------------------------------------
                */

                if ($newRank <= $currentRank) {

                    Log::warning(
                        'ESCALATION_BLOCKED_INVALID_RANK',
                        [
                            'inspection_id' => $inspection->id,
                            'user_id' => $user->id,
                            'current_rank' => $currentRank,
                            'new_rank' => $newRank,
                        ]
                    );

                    return ApiResponse::error(
                        'Invalid escalation: new penalty must be higher severity than current penalty.',
                        [],
                        422
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | EXECUTE ESCALATION
            |--------------------------------------------------------------------------
            */

            Log::info('ESCALATION_SERVICE_START', [
                'inspection_id' => $inspection->id,
                'user_id'       => $user->id,
            ]);

            $result = app(
                PenaltyEscalationService::class
            )->escalate(
                inspection: $inspection,
                newPenaltyType: $newPenaltyType,
                reason: $validated['reason'],
                newDueDate: $validated['new_due_date'],
                userId: $user->id
            );

            Log::info('ESCALATION_SUCCESS', [
                'inspection_id' => $inspection->id,
                'new_penalty_id' => $result->id ?? null,
                'user_id'       => $user->id,
            ]);

            return ApiResponse::success(
                $result,
                'Penalty escalated successfully'
            );

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {

            Log::warning('ESCALATION_UNAUTHORIZED', [
                'inspection_id' => $id,
                'user_id'       => auth()->id(),
            ]);

            return ApiResponse::error(
                'You are not authorized to escalate this inspection.',
                [],
                403
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            return ApiResponse::error(
                'Inspection not found.',
                [],
                404
            );

        } catch (\Throwable $e) {

            Log::error('ESCALATION_FAILED', [
                'inspection_id' => $id,
                'user_id'       => auth()->id(),
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
            ]);

            return ApiResponse::error(
                'Escalation failed.',
                [],
                500
            );
        }
    }
}