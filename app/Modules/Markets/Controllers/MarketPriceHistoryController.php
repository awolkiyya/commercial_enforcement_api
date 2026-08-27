<?php

namespace App\Modules\Markets\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MarketItem;
use App\Modules\Markets\Services\MarketPriceHistoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarketPriceHistoryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private MarketPriceHistoryService $service
    ) {}

    /**
     * =========================================================
     * GET PRICE HISTORY
     * =========================================================
     *
     * SECURITY:
     *
     * Authentication alone is NOT sufficient.
     *
     * The authenticated user must be authorized to view the
     * specific MarketItem whose price history is requested.
     *
     * This prevents an Inspector from simply changing:
     *
     * ?market_item_id=<another-item-uuid>
     *
     * and accessing data outside their authorization scope.
     */
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDATE REQUEST
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'market_item_id' => [
                'required',
                'uuid',
            ],

            'range' => [
                'nullable',
                Rule::in([
                    '7d',
                    '30d',
                    '90d',
                    '1y',
                    'custom',
                ]),
            ],

            'from' => [
                'nullable',
                'date',
            ],

            'to' => [
                'nullable',
                'date',
                'after_or_equal:from',
            ],

            'trend' => [
                'nullable',
                Rule::in([
                    'UP',
                    'DOWN',
                    'STABLE',
                ]),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | LOAD MARKET ITEM
        |--------------------------------------------------------------------------
        |
        | Never authorize based only on the supplied UUID.
        |
        | First resolve the actual model.
        |
        |--------------------------------------------------------------------------
        */

        $marketItem = MarketItem::query()
            ->findOrFail($validated['market_item_id']);

        /*
        |--------------------------------------------------------------------------
        | OBJECT-LEVEL AUTHORIZATION
        |--------------------------------------------------------------------------
        |
        | This is critical.
        |
        | The policy decides whether the authenticated user can
        | view THIS particular market item.
        |
        |--------------------------------------------------------------------------
        */

        $this->authorize(
            'view',
            $marketItem
        );

        /*
        |--------------------------------------------------------------------------
        | GET HISTORY
        |--------------------------------------------------------------------------
        |
        | Authorization has already passed.
        |
        |--------------------------------------------------------------------------
        */

        $result = $this->service->getHistory(
            $validated
        );

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'data' => [
                'minPrice' => $result->summary['minPrice'],

                'maxPrice' => $result->summary['maxPrice'],

                'averagePrice' =>
                    $result->summary['averagePrice'],

                'points' => $result->points,

                'trend' => $result->trend,

                'item' => $result->item ?? null,
            ],
        ]);
    }
}