<?php

namespace App\Modules\Governance\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SectorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            /**
             * =========================
             * IDENTITY
             * =========================
             */
            'id' => $this->id,

            /**
             * =========================
             * CORE SECTOR DATA
             * =========================
             */
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,

            /**
             * =========================
             * GOVERNANCE STRUCTURE
             * =========================
             */
            'cluster' => $this->cluster ? [
                'id' => $this->cluster->id,
                'name' => $this->cluster->name,
            ] : [
                'id' => $this->cluster_id,
                'name' => null,
            ],

            /**
             * =========================
             * STATUS
             * =========================
             */
            'is_active' => (bool) $this->is_active,

            /**
             * =========================
             * METADATA (UI + DEBUG + SORTING)
             * =========================
             */
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}