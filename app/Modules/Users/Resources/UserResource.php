<?php

namespace App\Modules\Users\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,

            // avatar (URL only)
            'avatar' => $this->avatar_file_id
            ? "/private-file/{$this->avatar_file_id}"
            : "",
            
            'is_active' => (bool) $this->is_active,

            'role' => $this->role,
            'level' => $this->level,

            /**
             * GOVERNANCE STRUCTURE
             */
            'sector' => $this->whenLoaded('sector', fn () => [
                'id' => $this->sector->id,
                'name' => $this->sector->name,
            ]),

            'city' => $this->whenLoaded('city', fn () => [
                'id' => $this->city->id,
                'name' => $this->city->name,
            ]),

            'subcity' => $this->whenLoaded('subcity', fn () => [
                'id' => $this->subcity->id,
                'name' => $this->subcity->name,
            ]),

            'wereda' => $this->whenLoaded('wereda', fn () => [
                'id' => $this->wereda->id,
                'name' => $this->wereda->name,
            ]),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}