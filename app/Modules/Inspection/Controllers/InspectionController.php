<?php

namespace App\Modules\Inspection\Controllers;

use App\Http\Controllers\Controller;


use App\Modules\Inspection\Requests\StoreInspectionRequest;
use App\Modules\Inspection\Resources\InspectionResource;
use App\Modules\Inspection\Services\InspectionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Inspection;
use App\Models\ViolationType;
use App\Support\PaginatesResponse;
use App\Queries\InspectionQuery;

use App\Models\User;



class InspectionController extends Controller
{
    use PaginatesResponse;

    public function __construct(
        private InspectionService $service
    ) {}

    public function store(StoreInspectionRequest $request): JsonResponse
{
    $user = $request->user();

    $inspection = $this->service->create(
        $request->validated(),
        $user->id
    );

    $inspection->load([
        'business.businessType',
        'business.city',
        'business.subcity',
        'business.wereda',
        'business.owner',
        'violations.violationType',
        'penalty.penaltyType',
        'resolution.resolvedBy',
        'inspector',
    ]);

    return ApiResponse::created(
        new InspectionResource($inspection),
        'Inspection created successfully'
    );
}


public function show(Request $request, string $id): JsonResponse
{
    try {
        $inspection = Inspection::with([

            // =========================
            // BUSINESS
            // =========================
            'business.businessType',
            'business.city',
            'business.subcity',
            'business.wereda',
            'business.owner',

            // =========================
            // VIOLATIONS
            // =========================
            'violations.violationType',

            // =========================
            // ENFORCEMENT
            // =========================
            'penalty.penaltyType',

            // =========================
            // RESOLUTION
            // =========================
            'resolution.resolvedBy',

            // =========================
            // INSPECTOR
            // =========================
            'inspector',

            // =========================
            // CLOSURE REQUESTS (IMPORTANT FIX)
            // =========================
            'closureRequests.requestedBy',
            'closureRequests.reviewedBy',
            'closureRequests.attachments',

        ])
        ->where('id', $id)
        ->first();

        if (!$inspection) {
            return ApiResponse::error(
                'Inspection not found',
                [],
                404
            );
        }

        return ApiResponse::success(
            new InspectionResource($inspection),
            'Inspection retrieved successfully'
        );

    } catch (\Throwable $e) {

        logger()->error('Inspection show failed', [
            'message' => $e->getMessage(),
            'inspection_id' => $id,
        ]);

        return ApiResponse::error(
            'Something went wrong while fetching inspection',
            [],
            500
        );
    }
}
public function myInspections(Request $request): JsonResponse
{
    $user = $request->user();

    $result = InspectionQuery::make($user)
        ->apply()
        ->withRelations()
        ->paginateWithSummary($request);

    return response()->json([
        'success' => true,
        'message' => 'My inspections retrieved successfully',

        'data' => InspectionResource::collection($result['data']),

        'pagination' => $result['pagination'],

        'summary' => $result['summary'],
    ]);
}

public function byBusiness(Request $request, string $businessId): JsonResponse
{
    $query = Inspection::with([
        'violations.violationType',
        'penalty.penaltyType',
        'inspector',
    ])->where('business_id', $businessId)
      ->latest();

    return $this->paginateResponse(
        $query,
        InspectionResource::class,
        'Business inspections retrieved successfully'
    );
}

public function trackPublicInspection(Request $request, string $inspectionNumber): JsonResponse
{
    try {

        $inspection = Inspection::with([
            'business.businessType',
            'business.city',
            'business.subcity',
            'business.wereda',
            'business.owner',

            'violations.violationType',
            'penalty.penaltyType',
            'resolution.resolvedBy',
            'inspector',
            ])
            ->where('inspection_number', $inspectionNumber)
            ->where('status', 'in_progress') // 👈 only active cases
            ->first();

        if (!$inspection) {
            return ApiResponse::error(
                'Inspection not found or not active',
                [],
                404
            );
        }

        return ApiResponse::success(
            new InspectionResource($inspection),
            'Inspection retrieved successfully'
        );

    } catch (\Throwable $e) {

        logger()->error('Public inspection tracking failed', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'inspection_number' => $inspectionNumber,
        ]);

        return ApiResponse::error(
            'Something went wrong while fetching inspection',
            [],
            500
        );
    }
}

public function export(Request $request)
{
    try {
        $user = $request->user();
        $format = $request->get('format', 'csv');

        $query = $this->buildInspectionQuery($request, $user);

        $inspections = $query
            ->with([
                'business.businessType',
                'business.city',
                'business.owner',
                'inspector',
            ])
            ->latest()
            ->get();

        return match ($format) {

            'csv' => $this->exportCsv($inspections, $user),

            'json' => response()->json([
                'success' => true,
                'message' => 'Export generated successfully',
                'data' => $inspections,
            ]),

            'pdf' => $this->exportPdf($inspections, $user),

            default => $this->exportCsv($inspections, $user),
        };

    } catch (\Throwable $e) {

        logger()->error('Inspection export failed', [
            'message' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Export failed',
        ], 500);
    }
}

private function buildInspectionQuery(Request $request, $user)
{
    return Inspection::query()
        ->where('inspector_id', $user->id)

        // =========================
        // SEARCH
        // =========================
        ->when($request->search, function ($q) use ($request) {
            $q->where(function ($sub) use ($request) {
                $sub->where('inspection_number', 'ILIKE', "%{$request->search}%")
                    ->orWhereHas('business', function ($b) use ($request) {
                        $b->where('name', 'ILIKE', "%{$request->search}%");
                    });
            });
        })

        // =========================
        // STATUS
        // =========================
        ->when($request->status && $request->status !== 'all', function ($q) use ($request) {
            $q->where('status', $request->status);
        })

        // =========================
        // TIME RANGE
        // =========================
        ->when($request->timeRange && $request->timeRange !== 'all', function ($q) use ($request) {

            match ($request->timeRange) {

                'today' => $q->whereDate('created_at', now()->toDateString()),

                'week' => $q->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]),

                'month' => $q->whereBetween('created_at', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ]),

                'year' => $q->whereBetween('created_at', [
                    now()->startOfYear(),
                    now()->endOfYear()
                ]),

                default => null,
            };
        });
}

private function exportCsv($inspections, $user)
{
    $filename = "inspections-{$user->id}-" . now()->timestamp . ".csv";

    $headers = [
        "Content-Type" => "text/csv",
        "Content-Disposition" => "attachment; filename={$filename}",
        "Cache-Control" => "no-cache, no-store, must-revalidate",
        "Pragma" => "no-cache",
        "Expires" => "0",
    ];

    return response()->stream(function () use ($inspections) {

        $file = fopen('php://output', 'w');

        // HEADER
        fputcsv($file, [
            'Inspection Number',
            'Business',
            'Status',
            'Inspector',
            'Created At',
        ]);

        foreach ($inspections as $inspection) {
            fputcsv($file, [
                $inspection->inspection_number,
                $inspection->business?->name ?? '',
                $inspection->status,
                $inspection->inspector?->name ?? '',
                optional($inspection->created_at)->toDateTimeString(),
            ]);
        }

        fclose($file);

    }, 200, $headers);
}

private function exportPdf($inspections, $user)
{
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
        'exports.inspections',
        [
            'inspections' => $inspections,
            'user' => $user,
        ]
    );

    return $pdf->download("inspections-{$user->id}-" . now()->timestamp . ".pdf");
}


public function dashboard(Request $request): JsonResponse
{
    $user = $request->user();

    return response()->json([
        'success' => true,
        'message' => 'Inspector dashboard loaded',

        'kpis' => $this->getKpis($user),

        'recent' => $this->getRecent($user),

        'action_required' => $this->getActionRequired($user),
    ]);
}

public function charts(Request $request): JsonResponse
{
    $user = $request->user();

    return response()->json([
        'success' => true,

        'trend' => $this->getTrend($user),

        'status_distribution' => $this->getStatusDistribution($user),

        'risk_distribution' => $this->getRiskDistribution($user),

        'business_load' => $this->getBusinessLoad($user),
    ]);
}

private function getKpis($user)
{
    $base = Inspection::where('inspector_id', $user->id);

    return [
        'total' => (clone $base)->count(),

        'in_progress' => (clone $base)->where('status', 'in_progress')->count(),

        'completed' => (clone $base)->where('status', 'completed')->count(),

        'overdue' => (clone $base)
            ->where('status', 'in_progress')
            ->where('created_at', '<', now()->subDays(7))
            ->count(),

        'high_risk' => (clone $base)
            ->whereHas('violations.violationType', fn ($q) =>
                $q->where('severity_level', 'high')
            )
            ->count(),
    ];
}

private function getRecent($user)
{
    return Inspection::where('inspector_id', $user->id)
        ->with(['business', 'inspector'])
        ->latest()
        ->limit(5)
        ->get()
        ->map(fn ($i) => [
            'id' => $i->id,
            'inspection_number' => $i->inspection_number,
            'business' => $i->business->name ?? null,
            'status' => $i->status,
            'created_at' => $i->created_at,
        ]);
}
private function getActionRequired($user)
{
    return Inspection::where('inspector_id', $user->id)

        ->where(function ($q) {
            $q->where('status', 'in_progress')
              ->orWhere('status', 'overdue');
        })

        ->orderBy('created_at', 'asc')

        ->limit(10)

        ->get()
        ->map(fn ($i) => [
            'id' => $i->id,
            'inspection_number' => $i->inspection_number,
            'business' => $i->business->name ?? null,
            'status' => $i->status,
            'priority' => $i->status === 'overdue' ? 'high' : 'medium',
        ]);
}
private function getTrend($user)
{
    return Inspection::selectRaw('DATE(created_at) as date, COUNT(*) as total')
        ->where('inspector_id', $user->id)
        ->where('created_at', '>=', now()->subDays(30))
        ->groupBy('date')
        ->orderBy('date')
        ->get();
}
private function getStatusDistribution($user)
{
    return Inspection::selectRaw('status, COUNT(*) as total')
        ->where('inspector_id', $user->id)
        ->groupBy('status')
        ->pluck('total', 'status');
}

private function getRiskDistribution($user)
{
    return DB::table('inspections')
        ->join('violations', 'inspections.id', '=', 'violations.inspection_id')
        ->join('violation_types', 'violations.violation_type_id', '=', 'violation_types.id')
        ->where('inspections.inspector_id', $user->id)
        ->selectRaw('violation_types.severity_level, COUNT(*) as total')
        ->groupBy('violation_types.severity_level')
        ->pluck('total', 'severity_level');
}

private function getBusinessLoad($user)
{
    return Inspection::query()
        ->join('businesses', 'inspections.business_id', '=', 'businesses.id')
        ->join('business_types', 'businesses.business_type_id', '=', 'business_types.id')
        ->where('inspections.inspector_id', $user->id)
        ->selectRaw('business_types.name as type, COUNT(*) as total')
        ->groupBy('business_types.name')
        ->orderByDesc('total')
        ->get();
}

public function inspectors(): JsonResponse
{
    try {
        $userId = auth()->id(); // or $request->user()->id

        $inspectors = User::query()
            ->where('role', 'INSPECTOR')
            ->where('id', '!=', $userId) // ✅ exclude current user
            ->select([
                'id',
                'name',
                'role',
            ])
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            $inspectors,
            'Inspectors retrieved successfully'
        );

    } catch (\Throwable $e) {

        logger()->error('Inspectors fetch failed', [
            'message' => $e->getMessage(),
        ]);

        return ApiResponse::error(
            'Failed to fetch inspectors',
            [],
            500
        );
    }
}



}