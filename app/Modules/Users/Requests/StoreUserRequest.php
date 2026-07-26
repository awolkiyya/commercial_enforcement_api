<?php

namespace App\Modules\Users\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'password' => 'required|string|min:6',

            'role' => 'required|in:SUPER_ADMIN,ADMIN,SUPERVISOR,INSPECTOR',
            'level' => 'nullable|in:CITY,SUBCITY,WEREDA',

            'city_id' => 'nullable|exists:cities,id',
            'subcity_id' => 'nullable|exists:subcities,id',
            'wereda_id' => 'nullable|exists:weredas,id',
            
            'is_active' => 'sometimes|boolean',

            // ✅ FIXED: file upload
            'avatar' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }
}