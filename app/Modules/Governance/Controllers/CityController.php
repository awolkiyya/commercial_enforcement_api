<?php
namespace App\Modules\Governance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use App\Support\ApiResponse;
use App\Support\PaginatesResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CityController extends Controller
{
    use AuthorizesRequests, PaginatesResponse;

    public function index(Request $request)
    {
    
        $query = City::query();
    
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
    
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
    
        $paginated = $query->paginate($request->get('per_page', 10));
    
        return ApiResponse::success(
            $paginated->items(),
            "Cities fetched successfully",
            [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ]
        );
    }
}