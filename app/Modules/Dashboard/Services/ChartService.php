<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Models\Inspection;
use App\Models\Violation;
use App\Models\Business;
use App\Models\Complaint;

class ChartService
{
    public function handle(User $user): array
    {
        $scope = $this->scope($user);

        return [
            // =========================
            // INSPECTIONS TREND (DYNAMIC)
            // =========================
            'inspections_trend' => $this->inspectionsTrend($user, $scope),

            // =========================
            // VIOLATIONS TREND
            // =========================
            'violations_trend' => $this->violationsTrend($user, $scope),

            // =========================
            // BUSINESS STATUS DISTRIBUTION
            // =========================
            'business_status' => $this->businessStatus($scope),

            // =========================
            // COMPLAINT STATUS DISTRIBUTION
            // =========================
            'complaint_status' => $this->complaintStatus($scope),

            // =========================
            // ENFORCEMENT OVERVIEW
            // =========================
            'enforcement_overview' => $this->enforcementOverview($scope),
        ];
    }

    /**
     * =========================================================
     * APPLY ROLE + GEOGRAPHIC SCOPE (CORE FILTER ENGINE)
     * =========================================================
     */
    private function scope(User $user): array
    {
        $businesses = Business::query();
        $inspections = Inspection::query();
        $violations = Violation::query();
        $complaints = Complaint::query();

        // =========================
        // GEOGRAPHIC FILTER (GLOBAL RULE)
        // =========================
        if ($user->role !== 'system_admin') {

            if ($user->administrative_level === 'city') {
                $businesses->where('city_id', $user->administrative_unit_id);
            }

            if ($user->administrative_level === 'subcity') {
                $businesses->where('subcity_id', $user->administrative_unit_id);
            }

            if ($user->administrative_level === 'wereda') {
                $businesses->where('wereda_id', $user->administrative_unit_id);
            }
        }

        // =========================
        // INSPECTOR FILTER
        // =========================
        if ($user->role === 'inspector') {
            $inspections->where('inspector_id', $user->id);
            $violations->where('inspector_id', $user->id);
        }

        // =========================
        // LINK FILTERING
        // =========================
        $businessIds = (clone $businesses)->pluck('id');

        $inspections->whereIn('business_id', $businessIds);
        $violations->whereIn('business_id', $businessIds);

        $inspectionIds = Inspection::whereIn('business_id', $businessIds)->pluck('id');

        $complaints->whereIn('inspection_id', $inspectionIds);

        return [
            'businesses' => $businesses,
            'inspections' => $inspections,
            'violations' => $violations,
            'complaints' => $complaints,
        ];
    }

    /**
     * =========================================================
     * INSPECTIONS TREND (HIERARCHICAL)
     * =========================================================
     */
    private function inspectionsTrend(User $user, array $scope): array
    {
        $inspections = $scope['inspections'];

        // CITY → GROUP BY SUBCITY
        if ($user->administrative_level === 'city') {
            return $inspections
                ->selectRaw('subcity_id, COUNT(*) as total')
                ->groupBy('subcity_id')
                ->with('subcity:id,name')
                ->get()
                ->map(fn ($row) => [
                    'label' => $row->subcity?->name ?? 'Unknown',
                    'total' => $row->total,
                ]);
        }

        // SUBCITY → GROUP BY WEREDA
        if ($user->administrative_level === 'subcity') {
            return $inspections
                ->selectRaw('wereda_id, COUNT(*) as total')
                ->groupBy('wereda_id')
                ->with('wereda:id,name')
                ->get()
                ->map(fn ($row) => [
                    'label' => $row->wereda?->name ?? 'Unknown',
                    'total' => $row->total,
                ]);
        }

        // WEREDA / INSPECTOR → MONTHLY TREND
        return $inspections
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as period, COUNT(*) as total')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->period,
                'total' => $row->total,
            ]);
    }

    /**
     * =========================================================
     * VIOLATIONS TREND (MONTHLY)
     * =========================================================
     */
    private function violationsTrend(User $user, array $scope): array
    {
        return $scope['violations']
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as period, COUNT(*) as total')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->period,
                'total' => $row->total,
            ]);
    }

    /**
     * =========================================================
     * BUSINESS STATUS
     * =========================================================
     */
    private function businessStatus(array $scope): array
    {
        return $scope['businesses']
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();
    }

    /**
     * =========================================================
     * COMPLAINT STATUS
     * =========================================================
     */
    private function complaintStatus(array $scope): array
    {
        return $scope['complaints']
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();
    }

    /**
     * =========================================================
     * ENFORCEMENT OVERVIEW
     * =========================================================
     */
    private function enforcementOverview(array $scope): array
    {
        return [
            'inspections' => $scope['inspections']->count(),
            'violations' => $scope['violations']->count(),
            'complaints' => $scope['complaints']->count(),
        ];
    }
}