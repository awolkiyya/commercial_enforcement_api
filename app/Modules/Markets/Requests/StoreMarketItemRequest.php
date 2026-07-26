<?php

namespace  App\Modules\Markets\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarketItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'uuid', 'exists:market_categories,id'],
            'name' => ['required', 'string'],
            'unit' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],

        ];
    }
}