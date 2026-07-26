<?php

namespace App\Modules\Governance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Wereda;
use Illuminate\Http\Request;
use App\Support\ApiResponse;
use App\Modules\Governance\Resources\WeredaResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class WeredaController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        // 🔐 AUTHORIZATION

        // 📊 BASE QUERY (EAGER LOAD RELATIONS SAFELY)
        $query = Wereda::with(['subcity.city']);

        // 🔎 SEARCH FILTER
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // 📌 BASIC SORTING (SAFE FOR UUID SYSTEMS)
        $query->orderBy('name', 'asc');

        // 📄 PAGINATION
        $perPage = (int) $request->get('per_page', 10);
        $paginated = $query->paginate($perPage);

        // 📦 RESPONSE (IMPORTANT: pass items properly)
        return ApiResponse::success(
            WeredaResource::collection($paginated->items()),
            'Weredas fetched successfully',
            [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ]
        );
    }
}