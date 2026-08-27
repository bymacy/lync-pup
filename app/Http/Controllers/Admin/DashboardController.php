<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentDocument;
use App\Models\Cohort;
use App\Models\CoordinatorAssignment;
use App\Models\InformationSheet;
use App\Models\ReadinessLevelAssessment;
use App\Models\Roadblock;
use App\Models\Startup;
use App\Support\ReadinessRubric;
use App\Support\RiskEngine;
use App\Support\VentureExitForm;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * "Incubation Progress" buckets. Not an official PUP-TBIDO rubric — the
     * official ReadinessRubric scores each of the 4 categories 0-9 with an
     * overall_score average in that same 0-9 range (see
     * ReadinessRubric::overallLabel, which buckets 0-9 into
     * Ideation/Development/Validation/Growth). This dashboard card instead
     * needs a 0-5 scale per the reference mockup, so overall_score is
     * rescaled (score / 9 * 5) and bucketed here. Invented for this view;
     * flagged for the user in the build summary.
     */
    protected const INCUBATION_BUCKETS = [
        'High Ready' => [4.21, 5.00],
        'Moderately Ready' => [3.41, 4.20],
        'Moderately Unready' => [2.61, 3.40],
        'Not Ready' => [1.81, 2.60],
        'Critically Unready' => [1.00, 1.80],
    ];

    protected const INCUBATION_COLORS = [
        'High Ready' => '#059669',
        'Moderately Ready' => '#65A30D',
        'Moderately Unready' => '#D97706',
        'Not Ready' => '#EA580C',
        'Critically Unready' => '#B91C1C',
    ];

    public function index(Request $request): View
    {
        $cohortId = $request->query('cohort');
        $readinessStage = $request->query('readinessStage', 'Pre-Assessment');
        if (! in_array($readinessStage, ['Pre-Assessment', 'Post-Assessment'], true)) {
            $readinessStage = 'Pre-Assessment';
        }

        $cohorts = Cohort::withCount('startups')
            ->orderByRaw("CASE WHEN status = 'Active' THEN 0 ELSE 1 END")
            ->orderBy('number')
            ->get();
        $selectedCohort = $cohortId ? $cohorts->firstWhere('cohort_id', (int) $cohortId) : null;

        $startupsQuery = Startup::query()->when($selectedCohort, fn ($q) => $q->where('cohort_id', $selectedCohort->cohort_id));
        $startupIds = (clone $startupsQuery)->pluck('startup_id');
        $totalStartups = $startupIds->count();

        return view('dashboard', [
            'cohorts' => $cohorts,
            'selectedCohort' => $selectedCohort,
            'readinessStage' => $readinessStage,
            'totalStartups' => $totalStartups,
            'stats' => $this->buildStatCards($startupIds, $totalStartups),
            'incubationProgress' => $this->buildIncubationProgress($startupIds),
            'riskClassification' => $this->buildRiskClassification($startupIds),
            'averageReadiness' => $this->buildAverageReadiness($startupIds, $readinessStage),
            'milestones' => $this->buildMilestoneCompletion($startupIds, $totalStartups),
        ]);
    }

    /**
     * Stat cards: Total Startup, Assessed Startup (Pre/Post RL split), At
     * Risk Startup (via RiskEngine), Intervention Provided (roadblocks
     * resolved this month). Sparklines are grounded in real weekly counts
     * where a date column makes that meaningful; "At Risk" has no
     * historical snapshot in the data model (risk is always current-state),
     * so its sparkline is decorative rather than computed.
     */
    protected function buildStatCards($startupIds, int $totalStartups): array
    {
        $preRlCount = ReadinessLevelAssessment::where('stage', 'Pre-Assessment')
            ->whereNotNull('overall_score')
            ->whereIn('startup_id', $startupIds)
            ->count();
        $postRlCount = ReadinessLevelAssessment::where('stage', 'Post-Assessment')
            ->whereNotNull('overall_score')
            ->whereIn('startup_id', $startupIds)
            ->count();
        $assessedCount = ReadinessLevelAssessment::whereIn('stage', ['Pre-Assessment', 'Post-Assessment'])
            ->whereNotNull('overall_score')
            ->whereIn('startup_id', $startupIds)
            ->distinct('startup_id')
            ->count('startup_id');

        $startupsForRisk = Startup::with(['informationSheet', 'activeCoordinatorAssignment', 'roadblocks', 'readinessAssessments', 'cohort'])
            ->whereIn('startup_id', $startupIds)
            ->get();
        $documentsByStartup = AssessmentDocument::whereIn('startup_id', $startupIds)
            ->get()->groupBy('startup_id');
        $atRiskCount = $startupsForRisk->filter(
            fn (Startup $s) => RiskEngine::assess($s, $documentsByStartup->get($s->startup_id))['score'] > 0
        )->count();
        $atRiskPct = $totalStartups > 0 ? round(($atRiskCount / $totalStartups) * 100, 1) : 0.0;

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $interventionCount = Roadblock::whereIn('startup_id', $startupIds)
            ->where('status', 'Resolved')
            ->whereBetween('resolved_at', [$monthStart, $monthEnd])
            ->count();

        return [
            'total_startup' => [
                'value' => $totalStartups,
                'sparkline' => $this->weeklyCounts(Startup::whereIn('startup_id', $startupIds), 'created_at'),
            ],
            'assessed_startup' => [
                'value' => $assessedCount,
                'pre_rl' => $preRlCount,
                'post_rl' => $postRlCount,
                'sparkline' => $this->weeklyCounts(
                    ReadinessLevelAssessment::whereIn('startup_id', $startupIds)->whereNotNull('overall_score'),
                    'created_at'
                ),
            ],
            'at_risk_startup' => [
                'value' => $atRiskCount,
                'percent_of_total' => $atRiskPct,
                // Decorative only — risk has no historical snapshot to trend against.
                'sparkline' => [2, 3, 2, 4, 3, max($atRiskCount, 1)],
            ],
            'intervention_provided' => [
                'value' => $interventionCount,
                'sparkline' => $this->weeklyCounts(
                    Roadblock::whereIn('startup_id', $startupIds)->where('status', 'Resolved'),
                    'resolved_at'
                ),
            ],
        ];
    }

    /**
     * Weekly counts for the last 6 weeks (oldest first) for a given query
     * scoped to a date column — used to draw real, data-grounded sparklines.
     */
    protected function weeklyCounts($query, string $dateColumn, int $weeks = 6): array
    {
        $counts = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();
            $counts[] = (clone $query)->whereBetween($dateColumn, [$weekStart, $weekEnd])->count();
        }

        return $counts;
    }

    /**
     * Incubation Progress donut: classifies each startup that has at least
     * one scored assessment (Post-Assessment preferred over Pre-Assessment,
     * matching the founder-side dashboard's own precedence) into the 0-5
     * scale buckets above.
     */
    protected function buildIncubationProgress($startupIds): array
    {
        $assessments = ReadinessLevelAssessment::whereIn('startup_id', $startupIds)
            ->whereIn('stage', ['Pre-Assessment', 'Post-Assessment'])
            ->whereNotNull('overall_score')
            ->get()
            ->groupBy('startup_id');

        $counts = collect(array_keys(self::INCUBATION_BUCKETS))->mapWithKeys(fn ($label) => [$label => 0])->all();
        $assessedTotal = 0;

        foreach ($assessments as $rows) {
            $chosen = $rows->firstWhere('stage', 'Post-Assessment') ?? $rows->firstWhere('stage', 'Pre-Assessment');
            if (! $chosen || $chosen->overall_score === null) {
                continue;
            }
            $rescaled = max(1.0, min(5.0, ($chosen->overall_score / 9) * 5));
            foreach (self::INCUBATION_BUCKETS as $label => [$min, $max]) {
                if ($rescaled >= $min && $rescaled <= $max) {
                    $counts[$label]++;
                    $assessedTotal++;
                    break;
                }
            }
        }

        $breakdown = collect(self::INCUBATION_BUCKETS)->keys()->map(fn ($label) => [
            'label' => $label,
            'count' => $counts[$label],
            'percent' => $assessedTotal > 0 ? round(($counts[$label] / $assessedTotal) * 100, 1) : 0.0,
            'color' => self::INCUBATION_COLORS[$label],
        ]);

        return [
            'total' => $assessedTotal,
            'breakdown' => $breakdown,
        ];
    }

    /** Mirrors RiskMonitoringController's aggregation, scoped to $startupIds. */
    protected function buildRiskClassification($startupIds): array
    {
        $startups = Startup::with(['informationSheet', 'activeCoordinatorAssignment', 'roadblocks', 'readinessAssessments', 'cohort'])
            ->whereIn('startup_id', $startupIds)
            ->get();

        $documentsByStartup = AssessmentDocument::whereIn('startup_id', $startupIds)
            ->get()->groupBy('startup_id');

        $assessments = $startups->mapWithKeys(fn (Startup $s) => [
            $s->startup_id => RiskEngine::assess($s, $documentsByStartup->get($s->startup_id)),
        ]);

        $total = $startups->count();
        $levelCounts = collect(['Critical', 'High', 'Moderate', 'Low', 'None'])->mapWithKeys(
            fn ($level) => [$level => $assessments->filter(fn ($a) => $a['level'] === $level)->count()]
        );

        $breakdown = $levelCounts->map(fn ($count, $level) => [
            'label' => $level,
            'count' => $count,
            'percent' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
            'color' => RiskEngine::LEVEL_COLORS[$level],
        ])->values();

        return [
            'total' => $total,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Average Readiness Level card: cross-startup AVERAGE score per
     * category (TRL/MRL/TMRL/SRL) for the selected stage, feeding the
     * shared <x-readiness-radar> component plus 4 category boxes.
     */
    protected function buildAverageReadiness($startupIds, string $stage): array
    {
        $row = ReadinessLevelAssessment::whereIn('startup_id', $startupIds)
            ->where('stage', $stage)
            ->selectRaw('AVG(trl_score) as trl, AVG(mrl_score) as mrl, AVG(tmrl_score) as tmrl, AVG(srl_score) as srl, AVG(overall_score) as overall, COUNT(*) as n')
            ->first();

        $hasData = $row && $row->n > 0;

        $scores = [
            'TRL' => $hasData && $row->trl !== null ? round($row->trl, 1) : 0.0,
            'MRL' => $hasData && $row->mrl !== null ? round($row->mrl, 1) : 0.0,
            'TMRL' => $hasData && $row->tmrl !== null ? round($row->tmrl, 1) : 0.0,
            'SRL' => $hasData && $row->srl !== null ? round($row->srl, 1) : 0.0,
        ];
        $overall = $hasData && $row->overall !== null ? round($row->overall, 1) : null;

        return [
            'has_data' => $hasData,
            'scores' => $scores,
            'overall_score' => $overall,
            'overall_label' => ReadinessRubric::overallLabel($overall),
            'startup_count' => $hasData ? (int) $row->n : 0,
        ];
    }

    /**
     * Milestone Completion: 8 invented milestones (no such list exists
     * elsewhere in the app) each expressed as % of scoped startups that
     * have reached it, derived from real, existing data.
     */
    protected function buildMilestoneCompletion($startupIds, int $totalStartups): array
    {
        if ($totalStartups === 0) {
            $empty = collect([
                'Profile Setup', 'Information Sheet', 'Assign Profile Coordinator', 'Pre-Assessment',
                'Assign Mentor', 'Active-Assessment', 'Post-Assessment', 'Venture Exit',
            ])->map(fn ($label) => ['label' => $label, 'percent' => 0.0]);

            return ['overall_percent' => 0.0, 'milestones' => $empty];
        }

        $profileSetup = Startup::whereIn('startup_id', $startupIds)
            ->whereNotNull('industry_sector')->where('industry_sector', '!=', '')
            ->whereNotNull('location')->where('location', '!=', '')
            ->whereNotNull('contact_phone')->where('contact_phone', '!=', '')
            ->count();

        $infoSheet = InformationSheet::whereIn('startup_id', $startupIds)->distinct('startup_id')->count('startup_id');

        $coordinatorAssigned = CoordinatorAssignment::whereIn('startup_id', $startupIds)
            ->where('assignment_status', 'Active')->distinct('startup_id')->count('startup_id');

        $preAssessment = ReadinessLevelAssessment::whereIn('startup_id', $startupIds)
            ->where('stage', 'Pre-Assessment')->whereNotNull('overall_score')
            ->distinct('startup_id')->count('startup_id');

        $mentorAssigned = Roadblock::whereIn('startup_id', $startupIds)
            ->whereNotNull('mentor_id')->distinct('startup_id')->count('startup_id');

        $activeAssessment = AssessmentDocument::whereIn('startup_id', $startupIds)
            ->where('stage', 'Active-Assessment')->whereIn('document_number', [6, 7, 8])
            ->select('startup_id')->groupBy('startup_id')
            ->havingRaw('COUNT(DISTINCT document_number) = 3')
            ->get()->count();

        $postAssessment = ReadinessLevelAssessment::whereIn('startup_id', $startupIds)
            ->where('stage', 'Post-Assessment')->whereNotNull('overall_score')
            ->distinct('startup_id')->count('startup_id');

        $ventureExit = AssessmentDocument::whereIn('startup_id', $startupIds)
            ->where('document_number', VentureExitForm::DOCUMENT_NUMBER)
            ->distinct('startup_id')->count('startup_id');

        $pct = fn ($count) => round(($count / $totalStartups) * 100, 1);

        $milestones = collect([
            ['label' => 'Profile Setup', 'percent' => $pct($profileSetup)],
            ['label' => 'Information Sheet', 'percent' => $pct($infoSheet)],
            ['label' => 'Assign Profile Coordinator', 'percent' => $pct($coordinatorAssigned)],
            ['label' => 'Pre-Assessment', 'percent' => $pct($preAssessment)],
            ['label' => 'Assign Mentor', 'percent' => $pct($mentorAssigned)],
            ['label' => 'Active-Assessment', 'percent' => $pct($activeAssessment)],
            ['label' => 'Post-Assessment', 'percent' => $pct($postAssessment)],
            ['label' => 'Venture Exit', 'percent' => $pct($ventureExit)],
        ]);

        return [
            'overall_percent' => round($milestones->avg('percent'), 1),
            'milestones' => $milestones,
        ];
    }
}
