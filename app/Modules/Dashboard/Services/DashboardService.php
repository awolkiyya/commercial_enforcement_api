<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;

class DashboardService
{
    public function __construct(
        private readonly OverviewService $overviewService,
        private readonly StatisticsService $statisticsService,
        private readonly ChartService $chartService,
        private readonly GeoIntelligenceService $geoIntelligenceService,
        private readonly ActivityService $activityService,
        private readonly AlertService $alertService,
    ) {}

    /**
     * =====================================================
     * MAIN DASHBOARD
     * =====================================================
     *
     * Returns only the essential data required for the
     * initial dashboard screen.
     *
     * Heavy data (charts, geo intelligence, activities)
     * should be loaded through their own endpoints.
     */
    public function index(User $user): array
    {
        return [
            'overview' => $this->overview($user),
        ];
    }

    /**
     * Dashboard Overview
     */
    public function overview(User $user): array
    {
        return $this->overviewService->handle($user);
    }

    /**
     * Dashboard Statistics
     */
    public function statistics(User $user): array
    {
        return $this->statisticsService->handle($user);
    }

    /**
     * Dashboard Charts
     */
    public function charts(User $user): array
    {
        return $this->chartService->handle($user);
    }

    /**
     * Geographic Intelligence
     */
    public function geo(User $user): array
    {
        return $this->geoIntelligenceService->handle($user);
    }

    /**
     * Recent Activities
     */
    public function activities(User $user): array
    {
        return $this->activityService->handle($user);
    }

    /**
     * Dashboard Alerts
     */
    public function alerts(User $user): array
    {
        return $this->alertService->handle($user);
    }
}