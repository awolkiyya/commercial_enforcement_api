<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Models\Business;
use App\Models\Inspection;
use App\Models\Violation;
use App\Models\Resolution;
use App\Models\Complaint;
use App\Models\InspectionClosureRequest;

class OverviewService
{
    public function handle(User $user): array
    {
        $query = $this->applyScope($user);

        return [
            // =========================
            // REGULATED ENTITY METRICS
            // =========================
            'total_entities' => (clone $query['entities'])->count(),

            'active_entities' => (clone $query['entities'])
                ->where('status', 'active')
                ->count(),

            'closed_entities' => (clone $query['entities'])
                ->where('status', 'closed')
                ->count(),

            // =========================
            // INSPECTION METRICS
            // =========================
            'total_inspections' => (clone $query['inspections'])->count(),

            'in_progress_inspections' => (clone $query['inspections'])
                ->where('status', 'in_progress')
                ->count(),

            'completed_inspections' => (clone $query['inspections'])
                ->where('status', 'completed')
                ->count(),

            // =========================
            // VIOLATION METRICS
            // =========================
            'total_violations' => (clone $query['violations'])->count(),

            // =========================
            // RESOLUTION METRICS
            // =========================
            'resolved_cases' => (clone $query['resolutions'])->count(),

            'permanently_closed_cases' => (clone $query['resolutions'])
                ->where('outcome', 'permanently_closed')
                ->count(),

            // =========================
            // COMPLAINT METRICS
            // =========================
            'total_complaints' => (clone $query['complaints'])->count(),

            'pending_complaints' => (clone $query['complaints'])
                ->where('status', 'submitted')
                ->count(),

            'approved_complaints' => (clone $query['complaints'])
                ->where('status', 'approved')
                ->count(),

            // =========================
            // WORKFLOW METRICS
            // =========================
            'pending_closure_requests' => (clone $query['closure_requests'])
                ->where('status', 'pending')
                ->count(),

            'approved_closure_requests' => (clone $query['closure_requests'])
                ->where('status', 'approved')
                ->count(),

            // =========================
            // SUPERVISOR INSIGHTS
            // =========================
            'supervisor_pending_closure_requests' => (clone $query['closure_requests'])
                ->where('status', 'pending')
                ->count(),

            'supervisor_pending_complaints' => (clone $query['complaints'])
                ->where('status', 'submitted')
                ->count(),
        ];
    }

    /**
     * APPLY ROLE + GEOGRAPHIC SCOPE
     * RULE:
     * ROLE = behavior
     * LEVEL = geographic filtering
     */
    private function applyScope(User $user): array
    {
        // =========================
        // BASE QUERIES
        // =========================
        $entities = Business::query(); // regulated commercial entities
        $inspections = Inspection::query();
        $violations = Violation::query();
        $resolutions = Resolution::query();
        $complaints = Complaint::query();
        $closureRequests = InspectionClosureRequest::query();

        // =========================
        // SYSTEM ADMIN (NO RESTRICTION)
        // =========================
        if ($user->role === 'system_admin') {
            return compact(
                'entities',
                'inspections',
                'violations',
                'resolutions',
                'complaints',
                'closureRequests'
            );
        }

        // =========================
        // GEOGRAPHIC SCOPE (ALL NON-SYSTEM USERS)
        // =========================
        if ($user->administrative_level === 'city') {
            $entities->where('city_id', $user->administrative_unit_id);
        }

        if ($user->administrative_level === 'subcity') {
            $entities->where('subcity_id', $user->administrative_unit_id);
        }

        if ($user->administrative_level === 'wereda') {
            $entities->where('wereda_id', $user->administrative_unit_id);
        }

        // =========================
        // INSPECTOR ROLE
        // =========================
        if ($user->role === 'inspector') {

            $inspections->where('inspector_id', $user->id);

            $violations->where('inspector_id', $user->id);

            $closureRequests->where('requested_by', $user->id);
        }

        // =========================
        // SUPERVISOR ROLE
        // =========================
        if ($user->role === 'supervisor') {

            // supervisor already inherits geographic scope above

            // focuses on workflow approvals
            $closureRequests->where('status', 'pending');
        }

        // =========================
        // RELATIONSHIP FILTERING
        // =========================
        $entityIds = (clone $entities)->pluck('id');

        $inspections->whereIn('business_id', $entityIds);
        $violations->whereIn('business_id', $entityIds);

        $inspectionIds = Inspection::whereIn('business_id', $entityIds)->pluck('id');

        $complaints->whereIn('inspection_id', $inspectionIds);
        $closureRequests->whereIn('inspection_id', $inspectionIds);
        $resolutions->whereIn('inspection_id', $inspectionIds);

        return compact(
            'entities',
            'inspections',
            'violations',
            'resolutions',
            'complaints',
            'closureRequests'
        );
    }
}