<?php

namespace App\Modules\Users\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function rules(): array
    {
        $userId = $this->route('user')?->id;
    
        return [
            'name' => 'sometimes|string|max:255',
    
            'email' => 'sometimes|email|unique:users,email,' . $userId,
    
            'phone' => 'nullable|string',
    
            'role' => 'required|in:SUPER_ADMIN,ADMIN,SUPERVISOR,INSPECTOR',
    
            'level' => 'nullable|in:CITY,SUBCITY,WEREDA',
    
            'city_id' => 'nullable|exists:cities,id',
    
            'subcity_id' => 'nullable|exists:subcities,id',
    
            'wereda_id' => 'nullable|exists:weredas,id',
    
            'avatar' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:2048',
    
            'is_active' => 'sometimes|boolean',
        ];
    }
}