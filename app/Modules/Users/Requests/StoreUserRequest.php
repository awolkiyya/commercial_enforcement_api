<?php

namespace App\Modules\Users\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the authenticated user is authorized
     * to create a new user.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name)
                ? trim($this->name)
                : $this->name,

            'email' => is_string($this->email)
                ? strtolower(trim($this->email))
                : $this->email,

            'phone' => is_string($this->phone)
                ? trim($this->phone)
                : $this->phone,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Basic User Information
            |--------------------------------------------------------------------------
            */

            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                'unique:users,email',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            /*
            |--------------------------------------------------------------------------
            | Password
            |--------------------------------------------------------------------------
            |
            | Production password policy:
            | - Minimum 12 characters
            | - Uppercase + lowercase
            | - Number
            | - Symbol
            | - Not found in known compromised-password databases
            | - Confirmation required
            |
            */

            'password' => [
                'required',
                'string',
                'min:12',

                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Role
            |--------------------------------------------------------------------------
            */

            'role' => [
                'required',
                Rule::in([
                    'SUPER_ADMIN',
                    'ADMIN',
                    'SUPERVISOR',
                    'INSPECTOR',
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Administrative Level
            |--------------------------------------------------------------------------
            */

            'level' => [
                'nullable',
                Rule::in([
                    'CITY',
                    'SUBCITY',
                    'WEREDA',
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Administrative Unit
            |--------------------------------------------------------------------------
            */

            'city_id' => [
                'nullable',
                'integer',
                'exists:cities,id',
            ],

            'subcity_id' => [
                'nullable',
                'integer',
                'exists:subcities,id',
            ],

            'wereda_id' => [
                'nullable',
                'integer',
                'exists:weredas,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Account Status
            |--------------------------------------------------------------------------
            */

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Avatar
            |--------------------------------------------------------------------------
            */

            'avatar' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Name
            |--------------------------------------------------------------------------
            */

            'name.required' => 'Name is required.',
            'name.string' => 'Name must be a valid string.',
            'name.min' => 'Name must be at least 2 characters.',
            'name.max' => 'Name may not be greater than 255 characters.',

            /*
            |--------------------------------------------------------------------------
            | Email
            |--------------------------------------------------------------------------
            */

            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'Email may not be greater than 255 characters.',
            'email.unique' => 'An account with this email address already exists.',

            /*
            |--------------------------------------------------------------------------
            | Phone
            |--------------------------------------------------------------------------
            */

            'phone.string' => 'Phone number must be a valid string.',
            'phone.max' => 'Phone number may not be greater than 30 characters.',

            /*
            |--------------------------------------------------------------------------
            | Password
            |--------------------------------------------------------------------------
            */

            'password.required' => 'Password is required.',
            'password.string' => 'Password must be a valid string.',
            'password.min' => 'Password must be at least 12 characters long.',

            /*
            |--------------------------------------------------------------------------
            | Role
            |--------------------------------------------------------------------------
            */

            'role.required' => 'User role is required.',
            'role.in' => 'The selected user role is invalid.',

            /*
            |--------------------------------------------------------------------------
            | Level
            |--------------------------------------------------------------------------
            */

            'level.in' => 'The selected administrative level is invalid.',

            /*
            |--------------------------------------------------------------------------
            | Administrative Units
            |--------------------------------------------------------------------------
            */

            'city_id.integer' => 'City ID must be a valid integer.',
            'city_id.exists' => 'The selected city does not exist.',

            'subcity_id.integer' => 'SubCity ID must be a valid integer.',
            'subcity_id.exists' => 'The selected subcity does not exist.',

            'wereda_id.integer' => 'Wereda ID must be a valid integer.',
            'wereda_id.exists' => 'The selected wereda does not exist.',

            /*
            |--------------------------------------------------------------------------
            | Account Status
            |--------------------------------------------------------------------------
            */

            'is_active.boolean' => 'Account status must be true or false.',

            /*
            |--------------------------------------------------------------------------
            | Avatar
            |--------------------------------------------------------------------------
            */

            'avatar.file' => 'Avatar must be a valid file.',
            'avatar.image' => 'Avatar must be an image.',
            'avatar.mimes' => 'Avatar must be a JPG, JPEG, PNG, or WEBP image.',
            'avatar.max' => 'Avatar may not be larger than 2 MB.',
        ];
    }

    /**
     * Custom attribute names for validation messages.
     */
    public function attributes(): array
    {
        return [
            'city_id' => 'city',
            'subcity_id' => 'subcity',
            'wereda_id' => 'wereda',
            'is_active' => 'account status',
        ];
    }
}