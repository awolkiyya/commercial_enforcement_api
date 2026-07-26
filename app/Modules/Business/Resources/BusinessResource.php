<?php

namespace App\Modules\Business\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            // =========================
            // CORE IDENTIFIERS
            // =========================
            'id' => $this->id,

            'business_name' => $this->name,
            'trade_name' => $this->trade_name,
            "description" => $this->description,
            // =========================
            // LEGAL IDENTIFIERS
            // =========================
            'license_number' => $this->license_number,
            'tin_number' => $this->tin_number,

            // =========================
            // STATUS (INSPECTOR READY)
            // =========================
            'status' => $this->status,
            'status_label' => ucfirst($this->status),

            'is_active' => $this->status === 'active',
            'is_pending' => $this->status === 'pending',
            'is_suspended' => $this->status === 'suspended',

            // =========================
            // LOCATION
            // =========================
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'map_link' => $this->latitude && $this->longitude
                    ? "https://www.google.com/maps?q={$this->latitude},{$this->longitude}"
                    : null,
            ],

            // =========================
            // OWNER (FULL CONTEXT)
            // =========================
            'owner' => $this->whenLoaded('owner', function () {
                return [
                    'id' => $this->owner->id,
                    'full_name' => $this->owner->full_name,
                    'national_id' => $this->owner->national_id,
                    'phone' => $this->owner->phone,
                    'email' => $this->owner->email,
                    'is_active' => $this->owner->is_active,
                ];
            }),

            // =========================
            // BUSINESS TYPE
            // =========================
            'business_type' => $this->whenLoaded('businessType', function () {
                return [
                    'id' => $this->businessType->id,
                    'name' => $this->businessType->name ?? null,
                ];
            }),

            // =========================
            // WEREDA (ADMIN CONTEXT)
            // =========================
            'city' => $this->whenLoaded('city', function () {
                return [
                    'id' => $this->city->id,
                    'name' => $this->city->name ?? null,
                ];
            }),

            'subcity' => $this->whenLoaded('subcity', function () {
                return [
                    'id' => $this->subcity->id,
                    'name' => $this->subcity->name ?? null,
                ];
            }),
            'wereda' => $this->whenLoaded('wereda', function () {
                return [
                    'id' => $this->wereda->id,
                    'name' => $this->wereda->name ?? null,
                ];
            }),

            // =========================
            // INSPECTOR (REGISTERED BY)
            // =========================
            'registered_by' => $this->whenLoaded('registeredBy', function () {
                return [
                    'id' => $this->registeredBy->id,
                    'name' => $this->registeredBy->name,
                    'role' => $this->registeredBy->role,
                ];
            }),

            // =========================
            // TIMESTAMPS
            // =========================
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // =========================
            // METADATA (USEFUL FOR UI)
            // =========================
            'meta' => [
                'has_owner' => !is_null($this->owner_id),
                'has_license' => !empty($this->license_number),
                'has_tin' => !empty($this->tin_number),
                'has_location' => !is_null($this->latitude) && !is_null($this->longitude),
            ],
        ];
    }
}