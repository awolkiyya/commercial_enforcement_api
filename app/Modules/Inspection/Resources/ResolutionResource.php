<?php

namespace App\Modules\Inspection\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResolutionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inspection_id' => $this->inspection_id,

            'outcome' => $this->outcome,
            'summary' => $this->summary,

            /* =========================
               RESOLVER INFO
            ========================= */
            'resolved_by' => [
                'id' => $this->resolved_by,
                'name' => $this->resolvedBy?->name,
            ],

            /* =========================
               INSPECTION SNAPSHOT
            ========================= */
            'inspection' => [
                'id' => $this->inspection?->id,
                'inspection_number' => $this->inspection?->inspection_number,
                'status' => $this->inspection?->status,

                /* =========================
                   BUSINESS SNAPSHOT
                ========================= */
                'business' => [
                    'id' => $this->inspection?->business?->id,
                    'name' => $this->inspection?->business?->name,

                    // ✅ ADDED: BUSINESS STATUS
                    'status' => $this->inspection?->business?->status,
                ],
            ],

            'resolved_at' => $this->resolved_at,
            'created_at' => $this->created_at,
        ];
    }
}