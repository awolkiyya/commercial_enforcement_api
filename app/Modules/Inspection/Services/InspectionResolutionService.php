<?php

namespace App\Modules\Inspection\Services;

use App\Models\Inspection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class InspectionResolutionService
{
    public function getResolution(Inspection $inspection)
    {
        return $inspection->resolution()->first();
    }

    /* =========================================================
        CREATE RESOLUTION
    ========================================================= */
    public function create(Inspection $inspection, array $data)
    {
        $this->guardCanResolve($inspection);
        $validated = $this->validateCreate($data);

        if ($inspection->resolution) {
            abort(409, 'Resolution already exists for this inspection');
        }

        Log::info('RESOLUTION_CREATE_INIT', [
            'inspection_id' => $inspection->id,
            'user_id' => Auth::id(),
        ]);

        return DB::transaction(function () use ($inspection, $validated) {

            $resolution = $inspection->resolution()->create([
                'outcome' => $validated['outcome'],
                'summary' => $validated['summary'],
                'resolved_by' => Auth::id(),
                'resolved_at' => now(),
            ]);

            $inspection->update([
                'status' => 'completed',
                'closed_by' => Auth::id(),
                'completed_at' => now(),
            ]);

            // IMPORTANT: unified business sync
            $this->syncBusinessState($inspection);

            Log::info('RESOLUTION_CREATED', [
                'inspection_id' => $inspection->id,
                'resolution_id' => $resolution->id,
            ]);

            return $resolution;
        });
    }

    /* =========================================================
        UPDATE RESOLUTION
    ========================================================= */
    public function update(Inspection $inspection, array $data)
    {
        $resolution = $inspection->resolution;

        if (!$resolution) {
            abort(404, 'Resolution not found');
        }

        $validated = $this->validateUpdate($data);

        Log::info('RESOLUTION_UPDATE_INIT', [
            'inspection_id' => $inspection->id,
            'resolution_id' => $resolution->id,
            'user_id' => Auth::id(),
        ]);

        return DB::transaction(function () use ($inspection, $resolution, $validated) {

            $resolution->update(array_filter($validated));

            // keep inspection consistent
            $inspection->update([
                'status' => 'completed',
                'closed_by' => Auth::id(),
                'completed_at' => now(),
            ]);

            // IMPORTANT: always recompute business state
            $this->syncBusinessState($inspection);

            Log::info('RESOLUTION_UPDATED', [
                'inspection_id' => $inspection->id,
                'resolution_id' => $resolution->id,
                'user_id' => Auth::id(),
            ]);

            return $resolution;
        });
    }

    /* =========================================================
        BUSINESS RULE ENGINE (SOURCE OF TRUTH)
    ========================================================= */
    private function syncBusinessState(Inspection $inspection): void
    {
        $resolution = $inspection->resolution()->first();
        $business = $inspection->business;

        if (!$resolution || !$business) {
            return;
        }

        // default safe state
        $businessStatus = 'active';

        match ($resolution->outcome) {
            'permanently_closed' => $businessStatus = 'closed',
            'closed_case' => $businessStatus = 'active',
            default => $businessStatus = 'active',
        };

        $business->update([
            'status' => $businessStatus,
        ]);
    }

    /* =========================================================
        GUARDS
    ========================================================= */
    private function guardCanResolve(Inspection $inspection): void
    {
        if ($inspection->status !== 'ready_for_resolution') {
            abort(422, 'Inspection is not ready for resolution');
        }
    }

    /* =========================================================
        VALIDATION
    ========================================================= */
    private function validateCreate(array $data): array
    {
        return validator($data, [
            'outcome' => [
                'required',
                Rule::in(['closed_case', 'permanently_closed']),
            ],
            'summary' => 'required|string|min:10|max:2000',
        ])->validate();
    }

    private function validateUpdate(array $data): array
    {
        return validator($data, [
            'outcome' => [
                'sometimes',
                Rule::in(['closed_case', 'permanently_closed']),
            ],
            'summary' => 'sometimes|string|min:10|max:2000',
        ])->validate();
    }
}