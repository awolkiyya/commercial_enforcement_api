<?php

namespace App\Modules\Markets\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MarketItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,

            'category_id' => $this->category_id,

            'category' => $this->whenLoaded('category'),

            'name' => $this->name,

            'unit' => $this->unit,

            'is_active' => $this->is_active,

            // ✅ ADD THIS
            'created_at' => $this->created_at?->toISOString(),

            // (optional but recommended)
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}