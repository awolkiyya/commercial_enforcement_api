<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Support\ApiResponse;
use App\Http\Resources\PenaltyTypeResource;
use App\Http\Resources\ViolationTypeResource;

class ReferenceDataController extends Controller
{
    public function violationTypes()
    {
        $perPage = request()->get('per_page', 10);

        $data = DB::table('violation_types')
            ->where('is_active', true)
            ->orderBy('severity_level', 'desc')
            ->paginate($perPage);

        return ApiResponse::success(
            ViolationTypeResource::collection($data),
            'Violation types fetched successfully',
            [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ]
        );
    }

    public function penaltyTypes()
    {
        $perPage = request()->get('per_page', 10);

        $data = DB::table('penalty_types')
            ->where('status', true)
            ->orderBy('category')
            ->paginate($perPage);

        return ApiResponse::success(
            PenaltyTypeResource::collection($data),
            'Penalty types fetched successfully',
            [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ]
        );
    }
}