<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Models\Business;
use App\Models\Inspection;
use App\Models\Violation;

class GeoIntelligenceService
{
    /**
     * MAIN ENTRY
     */
    public function handle(User $user): array
    {
        $scope = $this->scope($user);

        return [
            'violation_hotspots' => $this->violationHotspots($scope),
            'inspection_heatmap' => $this->inspectionHeatmap($scope),
            'coverage_gaps'      => $this->coverageGaps($scope),
            'risk_summary'       => $this->riskSummary($scope),
        ];
    }

    /**
     * =========================
     * SCOPE (ROLE + GEO FILTER)
     * =========================
     */
    private function scope(User $user): array
    {
        $businesses = Business::query();

        if ($user->administrative_level === 'city') {
            $businesses->where('city_id', $user->administrative_unit_id);
        }

        if ($user->administrative_level === 'subcity') {
            $businesses->where('subcity_id', $user->administrative_unit_id);
        }

        if ($user->administrative_level === 'wereda') {
            $businesses->where('wereda_id', $user->administrative_unit_id);
        }

        $businessIds = (clone $businesses)->pluck('id');

        return [
            'businesses'  => $businesses->get(['id', 'latitude', 'longitude']),
            'inspections' => Inspection::whereIn('business_id', $businessIds)
                                ->get(['id', 'business_id']),
            'violations'  => Violation::whereIn('business_id', $businessIds)
                                ->get(['id', 'business_id']),
        ];
    }

    /**
     * =========================
     * 1. VIOLATION HOTSPOTS
     * =========================
     */
    private function violationHotspots(array $scope): array
    {
        $zones = [];

        foreach ($scope['violations'] as $violation) {

            $business = $scope['businesses']
                ->firstWhere('id', $violation->business_id);

            if (!$business || !$business->latitude || !$business->longitude) {
                continue;
            }

            $key = $this->gridKey($business->latitude, $business->longitude);

            if (!isset($zones[$key])) {
                $zones[$key] = [
                    'lat' => $this->round($business->latitude),
                    'lng' => $this->round($business->longitude),
                    'count' => 0,
                ];
            }

            $zones[$key]['count']++;
        }

        return array_values($zones);
    }

    /**
     * =========================
     * 2. INSPECTION HEATMAP
     * =========================
     */
    private function inspectionHeatmap(array $scope): array
    {
        $heat = [];

        foreach ($scope['inspections'] as $inspection) {

            $business = $scope['businesses']
                ->firstWhere('id', $inspection->business_id);

            if (!$business || !$business->latitude || !$business->longitude) {
                continue;
            }

            $key = $this->gridKey($business->latitude, $business->longitude);

            if (!isset($heat[$key])) {
                $heat[$key] = [
                    'lat' => $this->round($business->latitude),
                    'lng' => $this->round($business->longitude),
                    'count' => 0,
                ];
            }

            $heat[$key]['count']++;
        }

        return array_values($heat);
    }

    /**
     * =========================
     * 3. COVERAGE GAPS
     * =========================
     */
    private function coverageGaps(array $scope): array
    {
        $inspected = $scope['inspections']
            ->pluck('business_id')
            ->unique()
            ->flip();

        $gaps = [];

        foreach ($scope['businesses'] as $business) {

            if (isset($inspected[$business->id])) {
                continue;
            }

            if (!$business->latitude || !$business->longitude) {
                continue;
            }

            $gaps[] = [
                'lat' => $this->round($business->latitude),
                'lng' => $this->round($business->longitude),
                'business_id' => $business->id,
            ];
        }

        return $gaps;
    }

    /**
     * =========================
     * 4. RISK SUMMARY (SIMPLE SCORE)
     * =========================
     */
    private function riskSummary(array $scope): array
    {
        $total = count($scope['businesses']);
        $violations = count($scope['violations']);

        return [
            'total_businesses' => $total,
            'total_violations' => $violations,
            'risk_ratio' => $total > 0 ? round($violations / $total, 3) : 0,
        ];
    }

    /**
     * =========================
     * HELPERS
     * =========================
     */
    private function gridKey($lat, $lng): string
    {
        return $this->round($lat) . ':' . $this->round($lng);
    }

    private function round($value, int $precision = 2): float
    {
        return round((float) $value, $precision);
    }
}