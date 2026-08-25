<?php

namespace App\Modules\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    private string $token;


    public function __construct($resource, string $token)
    {
        parent::__construct($resource);

        $this->token = $token;
    }



    public function toArray(Request $request): array
    {
        return [

            /**
             * =========================
             * USER CORE
             * =========================
             */
            'user' => [

                'id' => $this->id,

                'name' => $this->name,

                'email' => $this->email,

                'phone' => $this->phone,

                'avatar' => $this->avatar,


                'is_active' => (bool) $this->is_active,


                'role' => $this->role,

                'level' => $this->level,



                /**
                 * =========================
                 * GOVERNANCE STRUCTURE
                 * =========================
                 */

                'city' => $this->relationExists('city')
                    ? [
                        'id' => $this->city?->id,
                        'name' => $this->city?->name,
                    ]
                    : null,


                'subcity' => $this->relationExists('subcity')
                    ? [
                        'id' => $this->subcity?->id,
                        'name' => $this->subcity?->name,
                    ]
                    : null,


                'wereda' => $this->relationExists('wereda')
                    ? [
                        'id' => $this->wereda?->id,
                        'name' => $this->wereda?->name,
                    ]
                    : null,



                /**
                 * =========================
                 * AUTHORIZATION
                 * =========================
                 */

                'roles' => $this->getRoleNames(),

                'permissions' => $this->formatPermissions(),

            ],



            /**
             * =========================
             * AUTH TOKEN
             * =========================
             */

            'accessToken' => $this->token,

            'tokenType' => 'Bearer',

        ];
    }



    /**
     * Check if relationship exists and loaded
     */
    private function relationExists(string $relation): bool
    {
        return $this->resource->relationLoaded($relation)
            && !is_null($this->resource->{$relation});
    }




    /**
     * =========================
     * GROUP PERMISSIONS
     * =========================
     */

    private function formatPermissions(): array
    {
        $grouped = [];


        foreach ($this->getAllPermissions() as $permission) {

            $parts = explode('.', $permission->name);


            $module = $parts[0] ?? 'general';


            $action = $parts[1] ?? $permission->name;


            $grouped[$module][] = $action;
        }


        return $grouped;
    }
}