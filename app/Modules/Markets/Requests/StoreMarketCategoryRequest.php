<?php

namespace App\Modules\Markets\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarketCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:market_categories,name'],
            'description' => ['nullable', 'string'],
        ];
    }
}