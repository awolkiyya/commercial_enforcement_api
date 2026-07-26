<?php

namespace App\Modules\Markets\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMarketCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'nullable'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}