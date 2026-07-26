<?php

namespace App\Modules\Business\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Support\ApiResponse;
use App\Support\PaginatesResponse;

use App\Modules\Business\Services\BusinessService;
use App\Modules\Business\Resources\BusinessResource;

use App\Modules\Business\Requests\StoreBusinessRequest;
use App\Modules\Business\Requests\UpdateBusinessRequest;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Queries\BusinessQuery;



class BusinessController extends Controller
{
    use PaginatesResponse;

    public function __construct(
        protected BusinessService $service
    ) {}

   // =========================
    // LIST
    // =========================
    public function index(Request $request)
{
    try {
        $query = $this->service->list($request);

        $paginator = $query->paginate(
            $request->get('per_page', 15)
        );

        return ApiResponse::success(
            BusinessResource::collection($paginator),
            'Businesses retrieved successfully',
            [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ]
        );

    } catch (\Throwable $e) {
        return ApiResponse::error(
            $e->getMessage(),
            [],
            500
        );
    }
}

    public function scopedIndex(Request $request)
    {
        try {
            $query = $this->service->scopedList($request);
    
            return $this->paginateResponse(
                $query,
                BusinessResource::class
            );
    
        } catch (\Throwable $e) {
            report($e);
    
            return ApiResponse::error('Failed to load scoped businesses');
        }
    }

    // =========================
    // SHOW
    // =========================
    public function show($id)
{
    try {
        $business = $this->service->find($id);

        return ApiResponse::success(
            new BusinessResource($business)
        );

    } catch (ModelNotFoundException $e) {
        return ApiResponse::notFound('Business not found');

    } catch (\Throwable $e) {
        report($e); // IMPORTANT: log real error

        return ApiResponse::error('Failed to retrieve business');
    }
}

    // =========================
    // STORE
    // =========================
    public function store(StoreBusinessRequest $request)
    {
            $business = $this->service->create($request->validated());

            return ApiResponse::created(
                new BusinessResource($business),
                'Business created successfully'
            );
    }

    // =========================
    // UPDATE
    // =========================
    public function update(UpdateBusinessRequest $request, $id)
    {
        try {
            $business = $this->service->update($id, $request->validated());

            if (!$business) {
                return ApiResponse::notFound('Business not found');
            }

            return ApiResponse::success(
                new BusinessResource($business),
                'Business updated successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    // =========================
    // STATUS CHANGE
    // =========================
    public function changeStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:active,suspended,closed,informal,pending,approved'
            ]);

            $business = $this->service->changeStatus($id, $request->status);

            if (!$business) {
                return ApiResponse::notFound('Business not found');
            }

            return ApiResponse::success(
                new BusinessResource($business),
                'Status updated successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}