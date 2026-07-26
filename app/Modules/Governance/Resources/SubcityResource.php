<?php
namespace App\Modules\Governance\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubcityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code ?? null,

            /**
             * =========================
             * RELATIONSHIP CONTEXT
             * =========================
             */
            'city' => $this->city ? [
                'id' => $this->city->id,
                'name' => $this->city->name,
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
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}