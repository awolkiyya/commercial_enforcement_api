<?php
namespace App\Modules\Governance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Subcity;
use Illuminate\Http\Request;
use App\Support\ApiResponse;
use App\Support\PaginatesResponse;
use App\Modules\Governance\Resources\SubcityResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
class SubcityController extends Controller
{
    use AuthorizesRequests, PaginatesResponse;

    public function index(Request $request)
    {


        $query = Subcity::with(['city']);


        $query = $query->orderBy('name');

        $paginated = $query->paginate($request->get('per_page', 10));

        return ApiResponse::success(
            SubcityResource::collection($paginated),
            'Subcities fetched successfully',
            [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ]
        );
    }
}