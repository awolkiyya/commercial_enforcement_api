<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PenaltyTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // =========================
            // CORE IDENTITY
            // =========================
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'status' => (bool) $this->status,

            // =========================
            // BUSINESS RULE FLAGS
            // =========================
            'requires_due_date' => (bool) $this->requires_due_date,
            'is_final_action' => (bool) $this->is_final_action,
            'allows_escalation' => (bool) $this->allows_escalation,
            'stops_inspection_flow' => (bool) $this->stops_inspection_flow,

            // =========================
            // UI HINTS (optional but useful)
            // =========================
            'ui' => [
                'needs_deadline_input' => (bool) $this->requires_due_date,
                'is_terminal_state' => (bool) $this->is_final_action,
                'blocks_progression' => (bool) $this->stops_inspection_flow,
                'can_escalate' => (bool) $this->allows_escalation,
            ],
        ];
    }
}