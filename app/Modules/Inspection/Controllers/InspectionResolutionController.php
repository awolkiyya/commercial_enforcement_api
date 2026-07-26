<?php

namespace App\Modules\Inspection\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\Resolution;
use App\Support\ApiResponse;
use App\Modules\Inspection\Services\InspectionResolutionService;
use Illuminate\Http\Request;
use App\Support\PaginatesResponse;
use App\Modules\Inspection\Resources\ResolutionResource;
use App\Queries\ResolutionQuery;



class InspectionResolutionController extends Controller
{
    use PaginatesResponse;

    public function __construct(
        private readonly InspectionResolutionService $service
    ) {}

    public function index(Request $request)
    {
        try {
            $paginator = ResolutionQuery::make(auth()->user())
                ->apply()
                ->paginate($request);
    
            return ApiResponse::success(
                ResolutionResource::collection($paginator->items()),
                'Resolutions fetched successfully',
                [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ]
            );
    
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    public function show(Inspection $inspection)
    {
        $resolution = $this->service->getResolution($inspection);

        if (!$resolution) {
            return ApiResponse::error('No resolution found for this inspection', 404);
        }

        return ApiResponse::success($resolution);
    }

    public function store(Request $request, Inspection $inspection)
    {
        $resolution = $this->service->create($inspection, $request->all());

        return ApiResponse::created($resolution, 'Resolution created successfully');
    }

    public function update(Request $request, Inspection $inspection)
    {
        $resolution = $this->service->update($inspection, $request->all());

        return ApiResponse::success($resolution, 'Resolution updated successfully');
    }
}