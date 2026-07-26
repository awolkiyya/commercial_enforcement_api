<?php

namespace App\Support;

use App\Support\ApiResponse;

trait PaginatesResponse
{
    public function paginateResponse($query, $resource, $message = 'Data fetched successfully')
    {
        $perPage = request()->get('per_page', 10);

        $data = $query->paginate($perPage);

        return ApiResponse::success(
            $resource::collection($data),
            $message,
            [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
            ]
        );
    }
}