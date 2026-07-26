<?php

namespace App\Modules\Markets\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCityMarketPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'market_item_id' => ['required', 'uuid', 'exists:market_items,id'],
            'price' => ['required', 'numeric'],
        ];
    }
}