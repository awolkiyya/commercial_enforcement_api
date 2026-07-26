<?php

namespace App\Modules\Inspection\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClosureRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            // =========================
            // CORE IDENTIFIER
            // =========================
            'id' => $this->id,
            'status' => $this->status,
            'message' => $this->message,

            // =========================
            // REQUEST INFO
            // =========================
            'requested_by' => [
                'id' => $this->requested_by,
                'name' => $this->requestedBy?->name,
            ],

            'review' => [
                'reviewed_by' => $this->reviewed_by ? [
                    'id' => $this->reviewed_by,
                    'name' => $this->reviewedBy?->name,
                ] : null,

                'review_note' => $this->review_note,
                'reviewed_at' => $this->reviewed_at,
            ],

            // =========================
            // INSPECTION (LIGHTWEIGHT)
            // =========================
            'inspection' => [
                'id' => $this->inspection?->id,
                'inspection_number' => $this->inspection?->inspection_number,
                'status' => $this->inspection?->status,

                'inspector' => [
                    'id' => $this->inspection?->inspector_id,
                    'name' => $this->inspection?->inspector?->name,
                ],
            ],

            // =========================
            // BUSINESS (LOCATION CONTEXT)
            // =========================
            'business' => [
                'id' => $this->inspection?->business?->id,
                'name' => $this->inspection?->business?->name,

                'location' => [
                    'city_id' => $this->inspection?->business?->city_id,
                    'subcity_id' => $this->inspection?->business?->subcity_id,
                    'wereda_id' => $this->inspection?->business?->wereda_id,
                ],

                'license_number' => $this->inspection?->business?->license_number,
            ],

            // =========================
            // ATTACHMENTS (EVIDENCE)
            // =========================
            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'url' => $file->url ?? null,
                        'name' => $file->name ?? null,
                        'type' => $file->type ?? null,
                    ];
                });
            }),

            // =========================
            // TIMESTAMPS
            // =========================
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}