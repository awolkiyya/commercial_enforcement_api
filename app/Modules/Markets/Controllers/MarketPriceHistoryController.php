<?php

namespace App\Modules\Markets\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Modules\Markets\Services\MarketPriceHistoryService;

class MarketPriceHistoryController extends Controller
{
    public function __construct(
        private MarketPriceHistoryService $service
    ) {}

    /**
     * =========================================================
     * GET PRICE HISTORY
     * =========================================================
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'market_item_id' => ['required', 'uuid'],

            'range' => [
                'nullable',
                Rule::in(['7d', '30d', '90d', '1y', 'custom']),
            ],

            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date'],

            'trend' => [
                'nullable',
                Rule::in(['UP', 'DOWN', 'STABLE']),
            ],
        ]);

        $result = $this->service->getHistory($validated);

        return response()->json([
            'data' => [
                'minPrice'     => $result->summary['minPrice'],
                'maxPrice'     => $result->summary['maxPrice'],
                'averagePrice'  => $result->summary['averagePrice'],

                'points'       => $result->points,

                'trend'        => $result->trend,

                // IMPORTANT: ensure service loads relation OR fetch here
                'item'         => $result->item ?? null,
            ],
        ]);
    }
}