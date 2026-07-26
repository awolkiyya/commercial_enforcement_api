<?php

namespace App\Modules\Markets\Controllers;

use App\Http\Controllers\Controller;


use  App\Modules\Markets\Requests\StoreMarketItemRequest;
use  App\Modules\Markets\Requests\UpdateMarketItemRequest;
use  App\Modules\Markets\Resources\MarketItemResource;
use  App\Modules\Markets\Services\MarketItemService;
use Illuminate\Http\Request;

class MarketItemController extends Controller
{
    public function __construct(
        private MarketItemService $service
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'page'        => $request->integer('page', 1),
            'per_page'    => $request->integer('per_page', 20),
            'search'      => $request->input('search'),
            'category_id' => $request->input('category_id'),
            'status'      => $request->input('status'),
        ];
    
        $items = $this->service->list($filters);
    
        return MarketItemResource::collection($items);
    }

    public function store(StoreMarketItemRequest $request)
    {
        return new MarketItemResource(
            $this->service->create($request->validated())
        );
    }

    public function show(string $id)
    {
        return new MarketItemResource(
            $this->service->find($id)
        );
    }

    public function update(UpdateMarketItemRequest $request, string $id)
    {
        $item = $this->service->find($id);

        return new MarketItemResource(
            $this->service->update($item, $request->validated())
        );
    }

    public function destroy(string $id)
    {
        $item = $this->service->find($id);
    
        $this->service->delete($item);
    
        return response()->json([
            'message' => 'Market Item deleted successfully'
        ]);
    }
}