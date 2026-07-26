<?php

namespace App\Modules\Business\Controllers;


use App\Http\Controllers\Controller;

use App\Support\ApiResponse;
use App\Modules\Business\Services\BusinessTypeService;
use App\Modules\Business\Resources\BusinessTypeResource;

class BusinessTypeController extends Controller
{
    public function __construct(
        protected BusinessTypeService $service
    ) {}

    public function index()
    {
        try {
            $types = $this->service->getAll();

            return ApiResponse::success(
                BusinessTypeResource::collection($types),
                'Business types fetched successfully'
            );

        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}