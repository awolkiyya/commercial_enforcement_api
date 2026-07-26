<?php

namespace  App\Modules\Markets\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMarketItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'uuid', 'exists:market_categories,id'],
            'name' => ['sometimes', 'string'],
            'unit' => ['sometimes', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}