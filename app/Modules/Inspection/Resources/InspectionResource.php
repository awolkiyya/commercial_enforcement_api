<?php

namespace App\Modules\Inspection\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InspectionResource extends JsonResource
{
    public function toArray($request): array
    {
        $violations = collect($this->violations);

        $grouped = $violations
            ->groupBy(fn ($v) => $v->violationType->severity_level ?? 'unknown');

        $total = $violations->count();
        $critical = $grouped->get('critical', collect())->count();

        return [

            // =========================
            // IDENTITY LAYER
            // =========================
            'id' => $this->id,
            'inspection_number' => $this->inspection_number,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // =========================
            // TIMELINE
            // =========================
            'timeline' => [
                'started_at' => $this->started_at,
                'completed_at' => $this->completed_at,
                'duration_minutes' => $this->started_at && $this->completed_at
                    ? $this->started_at->diffInMinutes($this->completed_at)
                    : null,
            ],

            // =========================
            // BUSINESS
            // =========================
            'business' => [
                'id' => $this->business?->id,
                'name' => $this->business?->name ?? 'Unnamed Business',
                'trade_name' => $this->business?->trade_name,
                'description' => $this->business?->description,
                'license_number' => $this->business?->license_number,
                'tin_number' => $this->business?->tin_number,

                'type' => [
                    'id' => $this->business?->businessType?->id,
                    'name' => $this->business?->businessType?->name ?? 'Unknown',
                ],

                'status' => $this->business?->status,

                'location' => [
                    'address' => $this->buildAddress(),

                    'city' => [
                        'id' => $this->business?->city?->id,
                        'name' => $this->business?->city?->name,
                    ],
                    'subcity' => [
                        'id' => $this->business?->subcity?->id,
                        'name' => $this->business?->subcity?->name,
                    ],
                    'wereda' => [
                        'id' => $this->business?->wereda?->id,
                        'name' => $this->business?->wereda?->name,
                    ],
                ],

                'geo' => [
                    'latitude' => $this->business?->latitude,
                    'longitude' => $this->business?->longitude,
                ],

                'owner' => $this->business?->owner ? [
                    'id' => $this->business->owner->id,
                    'name' => $this->business->owner->name,
                    'phone' => $this->business->owner->phone,
                    'email' => $this->business->owner->email,
                ] : null,
            ],

            // =========================
            // INSPECTOR
            // =========================
            'inspector' => $this->inspector ? [
                'id' => $this->inspector->id,
                'name' => $this->inspector->name,
                'email' => $this->inspector->email,
                'role' => $this->inspector->role ?? null,
                'avatar' => $this->inspector->avatar_url ?? null,
            ] : null,

            // =========================
            // RISK
            // =========================
            'risk' => [
                'total_violations' => $total,
                'critical_violations' => $critical,

                'severity_distribution' => [
                    'low' => $grouped->get('low', collect())->count(),
                    'medium' => $grouped->get('medium', collect())->count(),
                    'high' => $grouped->get('high', collect())->count(),
                    'critical' => $critical,
                ],

                'compliance_status' => match (true) {
                    $total === 0 => 'compliant',
                    $critical > 0 => 'non_compliant',
                    default => 'warning',
                },

                'label' => match (true) {
                    $total === 0 => 'Compliant',
                    $critical > 0 => 'Critical Issues',
                    default => 'Needs Attention',
                },
            ],

            // =========================
            // VIOLATIONS
            // =========================
            'violations' => $violations->map(fn ($v) => [
                'id' => $v->id,
                'description' => $v->description,
                'type' => [
                    'id' => $v->violationType?->id,
                    'name' => $v->violationType?->name,
                    'severity_level' => $v->violationType?->severity_level,
                ],
            ])->values(),

            // =========================
            // ENFORCEMENT
            // =========================
            'enforcement' => [

                // PENALTY
                'penalty' => $this->penalty ? [
                    'id' => $this->penalty->id,
                    'penalty_type' => $this->penalty->penaltyType,
                    'category' => $this->penalty->penaltyType?->category,
                    'due_date' => $this->penalty->due_date,
                    'notes' => $this->penalty->notes,
                    'status' => $this->penalty->status,
                    'is_overdue' => $this->penalty->due_date
                        ? now()->gt($this->penalty->due_date)
                        : false,
                ] : null,

                // =========================
                // CLOSURE REQUESTS
                // =========================
                'inspection_closure_requests' => $this->closureRequests?->map(fn ($r) => [

                    'id' => $r->id,
                    'message' => $r->message,
                    'status' => $r->status,

                    'requested_by' => $r->requestedBy ? [
                        'id' => $r->requestedBy->id,
                        'name' => $r->requestedBy->name,
                        'email' => $r->requestedBy->email,
                    ] : null,

                    'review' => $r->reviewedBy ? [
                        'reviewed_by' => [
                            'id' => $r->reviewedBy->id,
                            'name' => $r->reviewedBy->name,
                            'email' => $r->reviewedBy->email,
                        ],
                        'review_note' => $r->review_note,
                        'reviewed_at' => $r->reviewed_at,
                    ] : null,

                    'attachments' => $r->attachments?->map(fn ($file) => [
                        'id' => $file->id,
                        'original_name' => $file->original_name,
                        'file_name' => $file->file_name,
                        'disk' => $file->disk,
                        'path' => $file->path,
                        'url' => url('storage/' . $file->path),
                        'mime_type' => $file->mime_type,
                        'extension' => $file->extension,
                        'size' => $file->size,
                        'category' => $file->category,
                        'visibility' => $file->visibility,
                        'checksum' => $file->checksum,
                        'created_at' => $file->created_at,
                    ])->values() ?? [],

                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,
                ])->values() ?? [],

                // =========================
                // RESOLUTION
                // =========================
                'resolution' => $this->resolution ? [

                    'id' => $this->resolution->id,
                    'outcome' => $this->resolution->outcome,
                    'outcome_label' => ucfirst(str_replace('_', ' ', $this->resolution->outcome)),
                    'summary' => $this->resolution->summary,

                    'resolved_at' => $this->resolution->resolved_at,
                    'resolved_at_human' => optional($this->resolution->resolved_at)->diffForHumans(),

                    'resolved_by' => $this->resolution->resolvedBy ? [
                        'id' => $this->resolution->resolvedBy->id,
                        'name' => $this->resolution->resolvedBy->name,
                        'email' => $this->resolution->resolvedBy->email,
                    ] : null,

                    'document' => $this->resolution->document_path ? [
                        'path' => $this->resolution->document_path,
                        'url' => url('storage/' . $this->resolution->document_path),
                        'type' => pathinfo($this->resolution->document_path, PATHINFO_EXTENSION),
                    ] : null,

                    'status' => $this->resolution->status,
                    'is_final' => $this->resolution->status === 'finalized',

                ] : null,
            ],
        ];
    }

    private function buildAddress(): string
    {
        $parts = array_filter([
            $this->business?->wereda?->name,
            $this->business?->subcity?->name,
            $this->business?->city?->name,
        ]);

        return implode(', ', $parts);
    }
}