<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Models\Inspection;
use App\Models\Violation;
use App\Models\Complaint;
use App\Models\InspectionClosureRequest;
use App\Models\Business;
use Illuminate\Support\Carbon;

class AlertService
{
    public function handle(User $user): array
    {
        $scope = $this->scope($user);

        return [
            'critical' => $this->criticalAlerts($scope),
            'warning'  => $this->warningAlerts($scope),
            'info'     => $this->infoAlerts($scope),
        ];
    }

    /**
     * =========================
     * CITY SCOPE (IMPORTANT CHANGE)
     * =========================
     */
    private function scope(User $user): array
    {
        $businesses = Business::query();
        $inspections = Inspection::query();
        $violations  = Violation::query();
        $complaints  = Complaint::query();
        $closures    = InspectionClosureRequest::query();

        /**
         * CITY ADMIN → only city-wide aggregation
         */
        if ($user->administrative_level === 'city') {

            $businesses->where('city_id', $user->administrative_unit_id);

            $inspections->whereHas('business', fn ($q) =>
                $q->where('city_id', $user->administrative_unit_id)
            );
        }

        /**
         * SUBCITY ADMIN
         */
        if ($user->administrative_level === 'subcity') {

            $businesses->where('subcity_id', $user->administrative_unit_id);

            $inspections->whereHas('business', fn ($q) =>
                $q->where('subcity_id', $user->administrative_unit_id)
            );
        }

        /**
         * WEREDA ADMIN
         */
        if ($user->administrative_level === 'wereda') {

            $businesses->where('wereda_id', $user->administrative_unit_id);

            $inspections->whereHas('business', fn ($q) =>
                $q->where('wereda_id', $user->administrative_unit_id)
            );
        }

        // link everything via inspections
        $inspectionIds = (clone $inspections)->pluck('id');

        $violations->whereIn('inspection_id', $inspectionIds);
        $complaints->whereIn('inspection_id', $inspectionIds);
        $closures->whereIn('inspection_id', $inspectionIds);

        return [
            'businesses' => $businesses,
            'inspections' => $inspections,
            'violations'  => $violations,
            'complaints'  => $complaints,
            'closures'    => $closures,
        ];
    }

    /**
     * =========================
     * CRITICAL ALERTS (CITY LEVEL)
     * =========================
     */
    private function criticalAlerts(array $scope): array
    {
        $alerts = [];

        // 1. City-wide violation surge
        $violationCount = (clone $scope['violations'])
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($violationCount >= 80) {
            $alerts[] = [
                'type' => 'city_violation_surge',
                'message' => 'City-wide violation surge detected (last 7 days)',
                'value' => $violationCount,
            ];
        }

        // 2. Closure backlog (city enforcement breakdown risk)
        $pendingClosures = (clone $scope['closures'])
            ->where('status', 'pending')
            ->count();

        if ($pendingClosures >= 40) {
            $alerts[] = [
                'type' => 'city_closure_backlog',
                'message' => 'City closure backlog is increasing',
                'value' => $pendingClosures,
            ];
        }

        return $alerts;
    }

    /**
     * =========================
     * WARNING ALERTS (CITY PATTERNS)
     * =========================
     */
    private function warningAlerts(array $scope): array
    {
        $alerts = [];

        // 1. Low inspection activity in city
        $recentInspections = (clone $scope['inspections'])
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($recentInspections < 20) {
            $alerts[] = [
                'type' => 'city_low_activity',
                'message' => 'City inspection activity is below expected level',
                'value' => $recentInspections,
            ];
        }

        // 2. Complaint spike (public dissatisfaction)
        $complaints = (clone $scope['complaints'])
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($complaints >= 30) {
            $alerts[] = [
                'type' => 'city_complaint_spike',
                'message' => 'Public complaints are increasing in the city',
                'value' => $complaints,
            ];
        }

        return $alerts;
    }

    /**
     * =========================
     * INFO ALERTS (CITY HEALTH)
     * =========================
     */
    private function infoAlerts(array $scope): array
    {
        $alerts = [];

        $totalInspections = $scope['inspections']->count();
        $totalBusinesses  = $scope['businesses']->count();

        if ($totalBusinesses > 0) {
            $coverage = round(($totalInspections / $totalBusinesses) * 100, 2);

            $alerts[] = [
                'type' => 'city_coverage_status',
                'message' => 'City inspection coverage rate',
                'coverage_percentage' => $coverage,
            ];
        }

        return $alerts;
    }
}