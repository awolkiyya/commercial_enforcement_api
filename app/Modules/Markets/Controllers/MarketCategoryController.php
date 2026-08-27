<?php

namespace App\Modules\Markets\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\Markets\Requests\StoreMarketCategoryRequest;
use App\Modules\Markets\Requests\UpdateMarketCategoryRequest;
use App\Modules\Markets\Resources\MarketCategoryResource;
use App\Modules\Markets\Services\MarketCategoryService;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MarketCategoryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private MarketCategoryService $service
    ) {}

    /**
     * LIST MARKET CATEGORIES
     *
     * Authentication alone is NOT sufficient.
     * Authorization is handled by the policy.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', \App\Models\MarketCategory::class);

        return MarketCategoryResource::collection(
            $this->service->list()
        );
    }

    /**
     * CREATE MARKET CATEGORY
     */
    public function store(
        StoreMarketCategoryRequest $request
    ): MarketCategoryResource {
        $this->authorize(
            'create',
            \App\Models\MarketCategory::class
        );

        return new MarketCategoryResource(
            $this->service->create(
                $request->validated()
            )
        );
    }

    /**
     * SHOW MARKET CATEGORY
     */
    public function show(string $id): MarketCategoryResource
    {
        $category = $this->service->find($id);

        $this->authorize('view', $category);

        return new MarketCategoryResource(
            $category
        );
    }

    /**
     * UPDATE MARKET CATEGORY
     */
    public function update(
        UpdateMarketCategoryRequest $request,
        string $id
    ): MarketCategoryResource {
        $category = $this->service->find($id);

        $this->authorize('update', $category);

        return new MarketCategoryResource(
            $this->service->update(
                $category,
                $request->validated()
            )
        );
    }

    /**
     * DELETE MARKET CATEGORY
     */
    public function destroy(string $id): JsonResponse
    {
        $category = $this->service->find($id);

        $this->authorize('delete', $category);

        $this->service->delete($category);

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }
}