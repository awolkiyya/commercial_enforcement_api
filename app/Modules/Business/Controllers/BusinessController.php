<?php

namespace App\Modules\Business\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use App\Models\Business;

use App\Support\ApiResponse;
use App\Support\PaginatesResponse;

use App\Modules\Business\Services\BusinessService;
use App\Modules\Business\Resources\BusinessResource;

use App\Modules\Business\Requests\StoreBusinessRequest;
use App\Modules\Business\Requests\UpdateBusinessRequest;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class BusinessController extends Controller
{
    use AuthorizesRequests, PaginatesResponse;

    public function __construct(
        protected BusinessService $service
    ) {}

    /*
    |--------------------------------------------------------------------------
    | LIST BUSINESSES
    |--------------------------------------------------------------------------
    |
    | Policy controls whether the actor can list businesses.
    | Service/query controls the geographic/data scope.
    |
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        try {

            $this->authorize('viewAny', Business::class);

            $query = $this->service->list($request);

            $perPage = min(
                max((int) $request->get('per_page', 15), 1),
                100
            );

            $paginator = $query->paginate($perPage);

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

        } catch (Throwable $e) {

            report($e);

            return ApiResponse::error(
                'Failed to load businesses',
                [],
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPED LIST
    |--------------------------------------------------------------------------
    */

    public function scopedIndex(Request $request)
    {
        try {

            $this->authorize('viewAny', Business::class);

            $query = $this->service->scopedList($request);

            return $this->paginateResponse(
                $query,
                BusinessResource::class
            );

        } catch (Throwable $e) {

            report($e);

            return ApiResponse::error(
                'Failed to load businesses',
                [],
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        try {

            $business = $this->service->find($id);

            /*
            |--------------------------------------------------------------------------
            | Object-level authorization
            |--------------------------------------------------------------------------
            |
            | Important:
            | Policy must determine whether THIS business may be viewed.
            |
            |--------------------------------------------------------------------------
            */

            $this->authorize('view', $business);

            return ApiResponse::success(
                new BusinessResource($business),
                'Business retrieved successfully'
            );

        } catch (ModelNotFoundException $e) {

            return ApiResponse::notFound(
                'Business not found'
            );

        } catch (Throwable $e) {

            report($e);

            return ApiResponse::error(
                'Failed to retrieve business',
                [],
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function store(StoreBusinessRequest $request)
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | SERVER-SIDE AUTHORIZATION
            |--------------------------------------------------------------------------
            */

            $this->authorize(
                'create',
                Business::class
            );

            $business = $this->service->create(
                $request->validated()
            );

            return ApiResponse::created(
                new BusinessResource($business),
                'Business created successfully'
            );

        } catch (Throwable $e) {

            report($e);

            return ApiResponse::error(
                'Failed to create business',
                [],
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateBusinessRequest $request,
        $id
    ) {
        try {

            $business = $this->service->find($id);

            /*
            |--------------------------------------------------------------------------
            | OBJECT-LEVEL AUTHORIZATION
            |--------------------------------------------------------------------------
            */

            $this->authorize(
                'update',
                $business
            );

            $updated = $this->service->update(
                $id,
                $request->validated()
            );

            if (!$updated) {
                return ApiResponse::notFound(
                    'Business not found'
                );
            }

            return ApiResponse::success(
                new BusinessResource($updated),
                'Business updated successfully'
            );

        } catch (ModelNotFoundException $e) {

            return ApiResponse::notFound(
                'Business not found'
            );

        } catch (Throwable $e) {

            report($e);

            return ApiResponse::error(
                'Failed to update business',
                [],
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CHANGE STATUS
    |--------------------------------------------------------------------------
    */

    public function changeStatus(
        Request $request,
        $id
    ) {
        try {

            $request->validate([
                'status' => [
                    'required',
                    'in:active,suspended,closed,informal,pending,approved',
                ],
            ]);

            $business = $this->service->find($id);

            /*
            |--------------------------------------------------------------------------
            | STATUS CHANGE IS A PRIVILEGED OPERATION
            |--------------------------------------------------------------------------
            */

            $this->authorize(
                'changeStatus',
                $business
            );

            $updated = $this->service->changeStatus(
                $id,
                $request->string('status')->toString()
            );

            if (!$updated) {
                return ApiResponse::notFound(
                    'Business not found'
                );
            }

            return ApiResponse::success(
                new BusinessResource($updated),
                'Status updated successfully'
            );

        } catch (ModelNotFoundException $e) {

            return ApiResponse::notFound(
                'Business not found'
            );

        } catch (Throwable $e) {

            report($e);

            return ApiResponse::error(
                'Failed to update business status',
                [],
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        try {

            $business = $this->service->find($id);

            $this->authorize(
                'delete',
                $business
            );

            $this->service->delete($business);

            return ApiResponse::success(
                null,
                'Business deleted successfully'
            );

        } catch (ModelNotFoundException $e) {

            return ApiResponse::notFound(
                'Business not found'
            );

        } catch (Throwable $e) {

            report($e);

            return ApiResponse::error(
                'Failed to delete business',
                [],
                500
            );
        }
    }
}