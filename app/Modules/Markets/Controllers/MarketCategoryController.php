<?php

namespace App\Modules\Markets\Controllers;


use App\Http\Controllers\Controller;

use App\Modules\Markets\Requests\StoreMarketCategoryRequest;
use App\Modules\Markets\Requests\UpdateMarketCategoryRequest;
use App\Modules\Markets\Resources\MarketCategoryResource;
use App\Modules\Markets\Services\MarketCategoryService;

class MarketCategoryController extends Controller
{
    public function __construct(
        private MarketCategoryService $service
    ) {}

    public function index()
    {
        return MarketCategoryResource::collection($this->service->list());
    }

    public function store(StoreMarketCategoryRequest $request)
    {
        return new MarketCategoryResource(
            $this->service->create($request->validated())
        );
    }

    public function show(string $id)
    {
        return new MarketCategoryResource(
            $this->service->find($id)
        );
    }

    public function update(UpdateMarketCategoryRequest $request, string $id)
    {
        $category = $this->service->find($id);

        return new MarketCategoryResource(
            $this->service->update($category, $request->validated())
        );
    }

    public function destroy(string $id)
{
    $category = $this->service->find($id);

    $this->service->delete($category);

    return response()->json([
        'message' => 'Category deleted successfully'
    ]);
}
}