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

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\Queries\ClosureRequestQuery;

class ClosureRequestController extends Controller
{
    use AuthorizesRequests, PaginatesResponse;

    /*
    |--------------------------------------------------------------------------
    | STORE CLOSURE REQUEST
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreClosureRequest $request,
        Inspection $inspection,
        ClosureRequestService $service
    ) {
        /*
        |--------------------------------------------------------------------------
        | OBJECT-LEVEL AUTHORIZATION
        |--------------------------------------------------------------------------
        |
        | The policy must determine whether the authenticated user
        | can submit a closure request for THIS inspection.
        |
        |--------------------------------------------------------------------------
        */

        $this->authorize(
            'createClosureRequest',
            $inspection
        );

        Log::info('CLOSURE_REQUEST_INIT', [
            'inspection_id' => $inspection->id,
            'user_id'       => Auth::id(),
        ]);

        $closure = $service->create(
            inspection: $inspection,
            data: $request->validated(),
            files: $request->file('files', []),
            userId: Auth::id(),
        );

        Log::info('CLOSURE_REQUEST_CREATED', [
            'inspection_id'     => $inspection->id,
            'closure_request_id' => $closure->id,
            'user_id'           => Auth::id(),
        ]);

        return ApiResponse::created(
            new ClosureRequestResource($closure),
            'Closure request submitted successfully'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LIST CLOSURE REQUESTS
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            /*
            |--------------------------------------------------------------------------
            | COLLECTION AUTHORIZATION
            |--------------------------------------------------------------------------
            |
            | Prevent an authenticated but unauthorized user from
            | accessing the closure-request management endpoint.
            |
            |--------------------------------------------------------------------------
            */

            $this->authorize(
                'viewAny',
                InspectionClosureRequest::class
            );

            Log::info('CLOSURE_REQUEST_INDEX', [
                'user_id' => $user->id,
                'filters' => $request->only([
                    'status',
                    'sort',
                    'per_page',
                ]),
            ]);

            /*
            |--------------------------------------------------------------------------
            | QUERY PIPELINE
            |--------------------------------------------------------------------------
            |
            | ClosureRequestQuery MUST ALSO enforce geographic/object
            | scope. Policy authorization protects entry to the endpoint;
            | query scoping protects the returned records.
            |
            |--------------------------------------------------------------------------
            */

            $query = ClosureRequestQuery::make($user)
                ->apply(
                    $request->only([
                        'status',
                        'sort',
                    ])
                );

            /*
            |--------------------------------------------------------------------------
            | SUMMARY
            |--------------------------------------------------------------------------
            |
            | Use the same secured base query so the summary cannot
            | expose counts from records outside the user's scope.
            |
            |--------------------------------------------------------------------------
            */

            $base = $query->baseQuery();

            $summary = [
                'total' => (clone $base)->count(),

                'pending' => (clone $base)
                    ->where('status', 'pending')
                    ->count(),

                'approved' => (clone $base)
                    ->where('status', 'approved')
                    ->count(),

                'rejected' => (clone $base)
                    ->where('status', 'rejected')
                    ->count(),
            ];

            /*
            |--------------------------------------------------------------------------
            | PAGINATION
            |--------------------------------------------------------------------------
            */

            $data = $query->paginate($request);

            /*
            |--------------------------------------------------------------------------
            | RESPONSE
            |--------------------------------------------------------------------------
            */

            return ApiResponse::success(
                ClosureRequestResource::collection(
                    $data->items()
                ),
                'Closure requests fetched successfully',
                [
                    'meta' => [
                        'current_page' => $data->currentPage(),
                        'per_page'     => $data->perPage(),
                        'total'        => $data->total(),
                        'last_page'    => $data->lastPage(),
                    ],

                    'summary' => $summary,
                ]
            );

        } catch (\Throwable $e) {

            Log::error(
                'CLOSURE_REQUEST_INDEX_FAILED',
                [
                    'message' => $e->getMessage(),
                    'user_id' => Auth::id(),
                ]
            );

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | MAKE DECISION
    |--------------------------------------------------------------------------
    */

    public function makeDecision(
        Request $request,
        InspectionClosureRequest $closureRequest,
        ClosureRequestService $service
    ) {
        /*
        |--------------------------------------------------------------------------
        | OBJECT-LEVEL AUTHORIZATION
        |--------------------------------------------------------------------------
        |
        | This is the most important authorization check in this
        | controller.
        |
        | It prevents a lower-privileged user from approving or
        | rejecting somebody else's closure request.
        |
        |--------------------------------------------------------------------------
        */

        $this->authorize(
            'decide',
            $closureRequest
        );

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'status' => [
                'required',
                'in:approved,rejected',
            ],

            'review_note' => [
                'required',
                'string',
                'min:5',
                'max:1000',
            ],
        ]);

        Log::info(
            'CLOSURE_REQUEST_DECISION_INIT',
            [
                'closure_request_id' => $closureRequest->id,
                'user_id'            => Auth::id(),
                'status'             => $validated['status'],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | BUSINESS STATE CHECK
        |--------------------------------------------------------------------------
        */

        if ($closureRequest->status !== 'pending') {

            return ApiResponse::error(
                'This closure request has already been processed',
                422
            );
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE VIA SERVICE
        |--------------------------------------------------------------------------
        */

        $updated = $service->decide(
            closureRequest: $closureRequest,
            status: $validated['status'],
            reviewNote: $validated['review_note'],
            userId: Auth::id()
        );

        Log::info(
            'CLOSURE_REQUEST_DECISION_DONE',
            [
                'closure_request_id' => $closureRequest->id,
                'status'             => $updated->status,
                'reviewed_by'        => Auth::id(),
            ]
        );

        return ApiResponse::success(
            new ClosureRequestResource($updated),
            'Decision saved successfully'
        );
    }
}