<?php

namespace App\Modules\Business\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BusinessTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'category' => $this->category,
            'priority_level' => $this->priority_level,
            'is_movable' => $this->is_movable,
            'requires_inspection' => $this->requires_inspection,
            'inspection_frequency_months' => $this->inspection_frequency_months,
        ];
    }
}