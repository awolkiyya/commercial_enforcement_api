<?php

namespace App\Modules\Governance\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WeredaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code ?? null,

            /**
             * =========================
             * HIERARCHY
             * =========================
             */

            'subcity_id' => $this->subcity?->id,

            'subcity' => $this->subcity ? [
                'id' => $this->subcity->id,
                'name' => $this->subcity->name,
                'code' => $this->subcity->code ?? null,
            ] : null,

            'city' => $this->city ? [
                'id' => $this->city->id,
                'name' => $this->city->name,
                'code' => $this->city->code ?? null,
            ] : null,

            /**
             * =========================
             * STATUS
             * =========================
             */
            'is_active' => (bool) $this->is_active,

            /**
             * =========================
             * META
             * =========================
             */
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}