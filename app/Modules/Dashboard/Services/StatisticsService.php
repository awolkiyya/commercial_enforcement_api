class StatisticsService
{
    public function handle(User $user): array
    {
        $scope = $this->scope($user);

        $inspections = $scope['inspections'];
        $violations  = $scope['violations'];
        $complaints  = $scope['complaints'];
        $resolutions = $scope['resolutions'];

        // =========================
        // RAW COUNTS (BASE DATA)
        // =========================
        $data = [
            'inspections' => $inspections->count(),
            'violations'  => $violations->count(),
            'complaints'  => $complaints->count(),
            'resolutions' => $resolutions->count(),
        ];

        // =========================
        // SIMPLE KPIs (FRONTEND READY)
        // =========================
        $kpis = [
            // 🔵 OPERATIONAL
            'inspection_completion_rate' =>
                $this->percent(
                    (clone $inspections)->where('status', 'completed')->count(),
                    $data['inspections']
                ),

            // 🔴 ENFORCEMENT
            'violation_rate' =>
                $this->ratio($data['violations'], $data['inspections']),

            'resolution_rate' =>
                $this->ratio($data['resolutions'], max($data['violations'], 1)),

            'unresolved_rate' =>
                $this->ratio(
                    max($data['violations'] - $data['resolutions'], 0),
                    max($data['violations'], 1)
                ),

            // 🟡 GOVERNANCE
            'complaint_rate' =>
                $this->ratio($data['complaints'], max($data['inspections'], 1)),

            'system_health' =>
                $this->systemHealth(
                    $data['inspections'],
                    $data['violations'],
                    $data['complaints'],
                    $data['resolutions']
                ),
        ];

        return [
            'data' => $data,
            'kpis' => $kpis,
        ];
    }

    // =========================
    // SIMPLE MATH HELPERS
    // =========================
    private function ratio($a, $b): float
    {
        return $b > 0 ? round($a / $b, 4) : 0;
    }

    private function percent($a, $b): float
    {
        return $this->ratio($a, $b) * 100;
    }

    /**
     * SINGLE SIMPLE HEALTH SCORE
     */
    private function systemHealth($inspections, $violations, $complaints, $resolutions): float
    {
        if ($inspections === 0) return 0;

        $violationImpact = $violations / $inspections;
        $complaintImpact = $complaints / $inspections;
        $resolutionBoost = $resolutions / max($violations, 1);

        $score = 100;

        $score -= $violationImpact * 50;
        $score -= $complaintImpact * 30;
        $score += $resolutionBoost * 40;

        return round(max(0, min(100, $score)), 2);
    }
}