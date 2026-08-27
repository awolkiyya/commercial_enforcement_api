<?php

namespace App\Modules\Markets\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MarketItem;

use App\Modules\Markets\Requests\StoreMarketItemRequest;
use App\Modules\Markets\Requests\UpdateMarketItemRequest;
use App\Modules\Markets\Resources\MarketItemResource;
use App\Modules\Markets\Services\MarketItemService;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MarketItemController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private MarketItemService $service
    ) {}

    /*
    |--------------------------------------------------------------------------
    | LIST MARKET ITEMS
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): AnonymousResourceCollection {
        /*
        |--------------------------------------------------------------------------
        | SERVER-SIDE AUTHORIZATION
        |--------------------------------------------------------------------------
        |
        | Do NOT rely on frontend route protection.
        | A logged-in user must also have permission to view items.
        |
        |--------------------------------------------------------------------------
        */

        $this->authorize(
            'viewAny',
            MarketItem::class
        );

        $filters = [
            'page'        => $request->integer('page', 1),
            'per_page'    => min(
                max($request->integer('per_page', 20), 1),
                100
            ),
            'search'      => $request->input('search'),
            'category_id' => $request->input('category_id'),
            'status'      => $request->input('status'),
        ];

        $items = $this->service->list($filters);

        return MarketItemResource::collection($items);
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE MARKET ITEM
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreMarketItemRequest $request
    ): MarketItemResource {
        /*
        |--------------------------------------------------------------------------
        | SERVER-SIDE CREATE AUTHORIZATION
        |--------------------------------------------------------------------------
        */

        $this->authorize(
            'create',
            MarketItem::class
        );

        $item = $this->service->create(
            $request->validated()
        );

        return new MarketItemResource($item);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW MARKET ITEM
    |--------------------------------------------------------------------------
    */

    public function show(
        string $id
    ): MarketItemResource {
        $item = $this->service->find($id);

        /*
        |--------------------------------------------------------------------------
        | Object-level authorization
        |--------------------------------------------------------------------------
        */

        $this->authorize(
            'view',
            $item
        );

        return new MarketItemResource($item);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE MARKET ITEM
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateMarketItemRequest $request,
        string $id
    ): MarketItemResource {
        $item = $this->service->find($id);

        /*
        |--------------------------------------------------------------------------
        | Object-level authorization
        |--------------------------------------------------------------------------
        */

        $this->authorize(
            'update',
            $item
        );

        $updated = $this->service->update(
            $item,
            $request->validated()
        );

        return new MarketItemResource($updated);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE MARKET ITEM
    |--------------------------------------------------------------------------
    */

    public function destroy(
        string $id
    ): JsonResponse {
        $item = $this->service->find($id);

        /*
        |--------------------------------------------------------------------------
        | Object-level authorization
        |--------------------------------------------------------------------------
        */

        $this->authorize(
            'delete',
            $item
        );

        $this->service->delete($item);

        return response()->json([
            'message' => 'Market Item deleted successfully',
        ]);
    }
}