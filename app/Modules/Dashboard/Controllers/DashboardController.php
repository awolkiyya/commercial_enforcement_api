<?php

namespace App\Modules\Dashboard\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Services\DashboardService;
use App\Support\ApiResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * MAIN DASHBOARD (Overview + all core data)
     */
    public function index(Request $request)
    {
        $data = $this->dashboardService->index($request->user());

        return ApiResponse::success(
            $data,
            'Dashboard loaded successfully'
        );
    }

    /**
     * ANALYTICS (Charts only)
     */
    public function charts(Request $request)
    {
        $data = $this->dashboardService->charts($request->user());

        return ApiResponse::success(
            $data,
            'Charts data retrieved successfully'
        );
    }

    /**
     * ACTIVITY FEED (paginated logs)
     */
    public function activities(Request $request)
    {
        $data = $this->dashboardService->activities(
            $request->user(),
            [
                'limit' => $request->get('limit', 20),
                'page' => $request->get('page', 1),
            ]
        );

        return ApiResponse::success(
            $data,
            'Activities retrieved successfully'
        );
    }

    /**
     * ALERTS / NOTIFICATIONS
     */
    public function alerts(Request $request)
    {
        $data = $this->dashboardService->alerts($request->user());

        return ApiResponse::success(
            $data,
            'Alerts retrieved successfully'
        );
    }
}