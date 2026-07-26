<?php

namespace App\Modules\Inspection\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\InspectionClosureRequest;
use App\Modules\Inspection\Requests\StoreClosureRequest;
use App\Modules\Inspection\Resources\ClosureRequestResource;
use App\Modules\Inspection\Services\ClosureRequestService;
use App\Support\ApiResponse;
use App\Support\PaginatesResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Queries\ClosureRequestQuery;


class ClosureRequestController extends Controller
{
    use PaginatesResponse;

    public function store(
        StoreClosureRequest $request,
        Inspection $inspection,
        ClosureRequestService $service
    ) {
        Log::info('CLOSURE_REQUEST_INIT', [
            'inspection_id' => $inspection->id,
            'user_id' => Auth::id(),
        ]);

        $closure = $service->create(
            inspection: $inspection,
            data: $request->validated(),
            files: $request->file('files', []),
            userId: Auth::id(),
        );

        Log::info('CLOSURE_REQUEST_CREATED', [
            'inspection_id' => $inspection->id,
            'closure_request_id' => $closure->id,
            'user_id' => Auth::id(),
        ]);

        return ApiResponse::created(
            new ClosureRequestResource($closure),
            'Closure request submitted successfully'
        );
    }
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
    
            Log::info('CLOSURE_REQUEST_INDEX', [
                'user_id' => $user->id,
                'filters' => $request->only(['status', 'sort', 'per_page']),
            ]);
    
            // =========================
            // QUERY PIPELINE
            // =========================
            $query = ClosureRequestQuery::make($user)
                ->apply($request->only(['status', 'sort']));
    
            // =========================
            // SUMMARY (SAFE CLONE FROM BASE QUERY)
            // =========================
            $base = $query->baseQuery();
    
            $summary = [
                'total' => (clone $base)->count(),
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'approved' => (clone $base)->where('status', 'approved')->count(),
                'rejected' => (clone $base)->where('status', 'rejected')->count(),
            ];
    
            // =========================
            // PAGINATION
            // =========================
            $data = $query->paginate($request);
    
            // =========================
            // RESPONSE (ApiResponse USED PROPERLY)
            // =========================
            return ApiResponse::success(
                ClosureRequestResource::collection($data->items()),
                'Closure requests fetched successfully',
                [
                    'meta' => [
                        'current_page' => $data->currentPage(),
                        'per_page' => $data->perPage(),
                        'total' => $data->total(),
                        'last_page' => $data->lastPage(),
                    ],
                    'summary' => $summary,
                ]
            );
    
        } catch (\Throwable $e) {
            Log::error('CLOSURE_REQUEST_INDEX_FAILED', [
                'message' => $e->getMessage(),
            ]);
    
            return ApiResponse::error($e->getMessage());
        }
    }

    public function makeDecision(
        Request $request,
        InspectionClosureRequest $closureRequest,
        ClosureRequestService $service
    ) {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'review_note' => 'required|string|min:5|max:1000',
        ]);
    
        Log::info('CLOSURE_REQUEST_DECISION_INIT', [
            'closure_request_id' => $closureRequest->id,
            'user_id' => Auth::id(),
            'status' => $validated['status'],
        ]);
    
        // =========================
        // BUSINESS RULE CHECK
        // =========================
        if ($closureRequest->status !== 'pending') {
            return ApiResponse::error(
                'This closure request has already been processed',
                422
            );
        }
    
        // =========================
        // UPDATE VIA SERVICE
        // =========================
        $updated = $service->decide(
            closureRequest: $closureRequest,
            status: $validated['status'],
            reviewNote: $validated['review_note'],
            userId: Auth::id()
        );
    
        Log::info('CLOSURE_REQUEST_DECISION_DONE', [
            'closure_request_id' => $closureRequest->id,
            'status' => $updated->status,
            'reviewed_by' => Auth::id(),
        ]);
    
        return ApiResponse::success(
            new ClosureRequestResource($updated),
            'Decision saved successfully'
        );
    }
}