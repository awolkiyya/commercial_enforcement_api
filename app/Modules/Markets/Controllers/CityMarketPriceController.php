<?php

namespace App\Modules\Markets\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\Markets\Requests\StoreCityMarketPriceRequest;
use App\Modules\Markets\Requests\UpdateCityMarketPriceRequest;
use App\Modules\Markets\Resources\CityMarketPriceResource;
use App\Modules\Markets\Services\CityMarketPriceService;
use Illuminate\Http\Request;



class CityMarketPriceController extends Controller
{
    public function __construct(
        private CityMarketPriceService $service
    ) {}
    public function index(Request $request)
    {
        $result = $this->service->listWithSummary($request->all());
    
        $items = $result['items'];
    
        return response()->json([
            'success' => true,
            'message' => 'Market prices fetched successfully',
    
            /**
             * =========================================================
             * RESOURCE OUTPUT (IMPORTANT FIX)
             * =========================================================
             */
            'data' => CityMarketPriceResource::collection($items),
    
            /**
             * =========================================================
             * SUMMARY
             * =========================================================
             */
            'summary' => $result['summary'],
    
            /**
             * =========================================================
             * PAGINATION META
             * =========================================================
             */
            'meta' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
    
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * CREATE
     */
    public function store(StoreCityMarketPriceRequest $request)
    {
        return new CityMarketPriceResource(
            $this->service->create(
                $request->validated(),
                $request->user()->id
            )
        );
    }

    /**
     * UPDATE
     */
    public function update(UpdateCityMarketPriceRequest $request, string $id)
    {
        return new CityMarketPriceResource(
            $this->service->update(
                $id,
                $request->validated(),
                $request->user()->id
            )
        );
    }

    /**
     * SINGLE
     */
    public function show(string $id)
    {
        return new CityMarketPriceResource(
            $this->service->find($id)
        );
    }


    /**
     * LATEST PRICE
     */
    public function latest(Request $request)
    {
        return response()->json(
            $this->service->latest($request->all())
        );
    }


    /**
     * UPSERT (safe insert/update)
     */
    public function upsert(Request $request)
    {
        return new CityMarketPriceResource(
            $this->service->upsert(
                $request->all(),
                $request->user()->id
            )
        );
    }

    public function destroy(string $id)
    {
        $itemPrice = $this->service->find($id);
    
        $this->service->delete($itemPrice);
    
        return response()->json([
            'message' => 'itemPrice deleted successfully'
        ]);
    }
}