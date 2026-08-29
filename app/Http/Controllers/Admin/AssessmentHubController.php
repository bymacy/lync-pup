<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentDocument;
use App\Models\EvaluationSchedule;
use App\Models\ReadinessLevelAssessment;
use App\Models\Startup;
use App\Support\ReadinessRubric;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentHubController extends Controller
{
    public function index(Request $request): View
    {
        // "Awaiting Schedule" / "Unscheduled" — startups pending approval that do NOT
        // currently have an active (Scheduled) evaluation. Once an evaluation is set,
        // the startup should disappear from here and show up under the Evaluation tab instead.
        $pendingStartups = Startup::with(['informationSheet', 'latestEvaluationSchedule'])
            ->pending()
            ->whereDoesntHave('evaluationSchedules', fn ($q) => $q->where('status', 'Scheduled'))
            ->orderBy('created_at')
            ->get();

        // Once a startup's Information Sheet is approved, they're done with
        // the evaluation stage — their schedule row (even if still
        // 'Scheduled') should stop showing up here and live under the
        // Approved tab instead.
        $scheduledToday = EvaluationSchedule::with('startup')
            ->where('status', 'Scheduled')
            ->whereDate('evaluation_date', now()->toDateString())
            ->whereDoesntHave('startup.informationSheet', fn ($q) => $q->where('approval_status', 'Approved'))
            ->orderBy('start_time')
            ->get();

        $scheduled = EvaluationSchedule::with('startup.informationSheet')
            ->where('status', 'Scheduled')
            ->get();

        $activeSchedules = $scheduled->reject(
            fn ($row) => $row->startup?->informationSheet?->approval_status === 'Approved'
        );

        $todayEvaluations = $activeSchedules->filter->isToday()->sortBy('start_time')->values();
        $upcomingEvaluations = $activeSchedules->filter->isUpcoming()->sortBy(['evaluation_date', 'start_time'])->values();
        $missedEvaluations = $activeSchedules->filter->isMissed()->sortByDesc('evaluation_date')->values();

        $approvedStartups = Startup::with('informationSheet')
            ->whereHas('informationSheet', fn ($q) => $q->where('approval_status', 'Approved'))
            ->orderBy('company_name')
            ->get();

        $bookedSlots = $scheduled
            ->filter(fn ($row) => $row->evaluation_date->gte(now()->startOfDay()))
            ->groupBy(fn ($row) => $row->evaluation_date->format('Y-m-d'))
            ->map(fn ($rows) => $rows->map(fn ($row) => [
                'id' => $row->evaluation_schedule_id,
                'start_time' => substr($row->start_time, 0, 5),
            ])->values())
            ->toArray();

        // ============ Assessment tab ============
        // Only Approved startups get assessed — same eligible list as the
        // Information Sheet tab's "Approved" sub-tab, since a startup needs
        // an approved information sheet before formal TRL/MRL/TMRL/SRL
        // scoring is meaningful.
        $assessableStartups = $approvedStartups;

        // "All Startup" (no id in the query string) is the real default —
        // it lands on the overview table below, not on some arbitrarily
        // first startup's detail view.
        $assessmentStartupParam = $request->query('assessment_startup');
        $selectedStartup = $assessmentStartupParam
            ? $assessableStartups->firstWhere('startup_id', (int) $assessmentStartupParam)
            : null;

        // Arriving from the sidebar (no query string) always lands on
        // Overview + "All Startup" — the assessment tab never resumes some
        // previously visited stage.
        $selectedStage = in_array($request->query('stage'), [...ReadinessRubric::STAGES, 'Overview', 'Reports'], true)
            ? $request->query('stage')
            : 'Overview';

        // Not persisted until Save Assessment is actually clicked —
        // firstOrNew (not firstOrCreate) so simply viewing an unscored
        // startup/stage combo doesn't write an empty row.
        $currentAssessment = $selectedStartup
            ? ReadinessLevelAssessment::firstOrNew([
                'startup_id' => $selectedStartup->startup_id,
                'stage' => $selectedStage,
            ])
            : null;

        // The sidebar summary card tracks whichever stage tab is currently
        // active (its header literally reads "PRE-ASSESSMENT" / "POST-
        // ASSESSMENT" / etc.) — only ever the persisted row, never the
        // in-progress firstOrNew() draft above.
        $stageSummary = $selectedStartup
            ? ReadinessLevelAssessment::where('startup_id', $selectedStartup->startup_id)
                ->where('stage', $selectedStage)
                ->first()
            : null;

        // Active-Assessment's Document 6/7/8 forms, keyed by document number,
        // for whichever startup is currently selected.
        $activeDocuments = $selectedStartup
            ? AssessmentDocument::where('startup_id', $selectedStartup->startup_id)
                ->where('stage', 'Active-Assessment')
                ->get()
                ->keyBy('document_number')
            : collect();

        // Venture Exit's Startup Exit Form (document 13) — its "Highest
        // Level" fields prefill from this same startup's saved
        // Post-Assessment scores.
        $ventureExitDocument = $selectedStartup
            ? AssessmentDocument::where('startup_id', $selectedStartup->startup_id)
                ->where('stage', 'Venture Exit')
                ->where('document_number', \App\Support\VentureExitForm::DOCUMENT_NUMBER)
                ->first()
            : null;

        $postAssessmentSummary = $selectedStartup
            ? ReadinessLevelAssessment::where('startup_id', $selectedStartup->startup_id)
                ->where('stage', 'Post-Assessment')
                ->first()
            : null;

        // ============ Assessment tab: Reports ============
        // The Reports stage tab lists this startup's previously "Save to
        // Reports"-d exports — nothing to show without a startup selected.
        $savedReports = $selectedStartup
            ? $selectedStartup->savedReports()->latest()->get()
            : collect();

        // ============ Assessment tab: "All Startup" overview table ============
        // One row per assessable startup, with a completed/not-started pill
        // for each stage/RL-type combo — "Document 6/7/8" pills are
        // "completed" once that document has been saved at least once.
        $pillDefinitions = [
            ['label' => 'PRE - TRL', 'stage' => 'Pre-Assessment', 'type' => 'TRL', 'nav_stage' => 'Pre-Assessment'],
            ['label' => 'PRE - MRL', 'stage' => 'Pre-Assessment', 'type' => 'MRL', 'nav_stage' => 'Pre-Assessment'],
            ['label' => 'PRE - SRL', 'stage' => 'Pre-Assessment', 'type' => 'SRL', 'nav_stage' => 'Pre-Assessment'],
            ['label' => 'PRE - TMRL', 'stage' => 'Pre-Assessment', 'type' => 'TMRL', 'nav_stage' => 'Pre-Assessment'],
            ['label' => 'DOCUMENT 6', 'document' => 6, 'nav_stage' => 'Active-Assessment'],
            ['label' => 'DOCUMENT 7', 'document' => 7, 'nav_stage' => 'Active-Assessment'],
            ['label' => 'DOCUMENT 8', 'document' => 8, 'nav_stage' => 'Active-Assessment'],
            ['label' => 'POST - TRL', 'stage' => 'Post-Assessment', 'type' => 'TRL', 'nav_stage' => 'Post-Assessment'],
            ['label' => 'POST - MRL', 'stage' => 'Post-Assessment', 'type' => 'MRL', 'nav_stage' => 'Post-Assessment'],
            ['label' => 'POST - SRL', 'stage' => 'Post-Assessment', 'type' => 'SRL', 'nav_stage' => 'Post-Assessment'],
            ['label' => 'POST - TMRL', 'stage' => 'Post-Assessment', 'type' => 'TMRL', 'nav_stage' => 'Post-Assessment'],
            ['label' => 'VENTURE EXIT', 'document' => \App\Support\VentureExitForm::DOCUMENT_NUMBER, 'nav_stage' => 'Venture Exit'],
        ];

        $assessmentsByStartup = ReadinessLevelAssessment::whereIn('startup_id', $assessableStartups->pluck('startup_id'))
            ->get()
            ->groupBy('startup_id');

        // Not scoped to a single stage — Document 6/7/8 only ever get
        // created under Active-Assessment and document 13 (the Startup
        // Exit Form) only under Venture Exit, so document numbers never
        // collide across stages.
        $documentsByStartup = AssessmentDocument::whereIn('startup_id', $assessableStartups->pluck('startup_id'))
            ->get()
            ->groupBy('startup_id');

        // When a specific startup is selected, the Overview table (only
        // reachable while a startup is selected via its own "Overview"
        // stage tab) should show just that startup's row, not every
        // assessable startup.
        $overviewRows = $assessableStartups
            ->when($selectedStartup, fn ($startups) => $startups->where('startup_id', $selectedStartup->startup_id))
            ->values()
            ->map(function (Startup $s) use ($pillDefinitions, $assessmentsByStartup, $documentsByStartup) {
                $byStage = $assessmentsByStartup->get($s->startup_id, collect())->keyBy('stage');
                $byDocument = $documentsByStartup->get($s->startup_id, collect())->keyBy('document_number');

                $pills = collect($pillDefinitions)->map(function ($def) use ($byStage, $byDocument) {
                    if (! empty($def['document'])) {
                        return ['label' => $def['label'], 'completed' => $byDocument->has($def['document']), 'nav_stage' => $def['nav_stage']];
                    }

                    $row = $byStage->get($def['stage']);

                    $completed = ! empty($def['aggregate'])
                        ? (bool) ($row && collect(ReadinessRubric::TYPES)->contains(fn ($t) => $row->scoreFor($t) !== null))
                        : (bool) ($row && $row->scoreFor($def['type']) !== null);

                    return ['label' => $def['label'], 'completed' => $completed, 'nav_stage' => $def['nav_stage']];
                });

                return [
                    'startup' => $s,
                    'pills' => $pills,
                    'completed_count' => $pills->where('completed', true)->count(),
                    'not_started_count' => $pills->where('completed', false)->count(),
                ];
            });

        // Venture Exit's "Save Assessment" should warn (not silently allow)
        // when the startup still has other assessments/documents that were
        // never started — reuses the same pill definitions/completion rule
        // as the Overview table above, just scoped to this one startup and
        // excluding Venture Exit's own pill.
        $incompleteAssessments = collect();
        if ($selectedStartup) {
            $byStage = $assessmentsByStartup->get($selectedStartup->startup_id, collect())->keyBy('stage');
            $byDocument = $documentsByStartup->get($selectedStartup->startup_id, collect())->keyBy('document_number');

            $incompleteAssessments = collect($pillDefinitions)
                ->reject(fn ($def) => ($def['document'] ?? null) === \App\Support\VentureExitForm::DOCUMENT_NUMBER)
                ->reject(function ($def) use ($byStage, $byDocument) {
                    if (! empty($def['document'])) {
                        return $byDocument->has($def['document']);
                    }

                    $row = $byStage->get($def['stage']);

                    return (bool) ($row && $row->scoreFor($def['type']) !== null);
                })
                ->pluck('label')
                ->values();
        }

        return view('admin.assessment-hub.index', [
            'pendingStartups' => $pendingStartups,
            'scheduledToday' => $scheduledToday,
            'todayEvaluations' => $todayEvaluations,
            'upcomingEvaluations' => $upcomingEvaluations,
            'missedEvaluations' => $missedEvaluations,
            'approvedStartups' => $approvedStartups,
            'timeSlots' => EvaluationSchedule::TIME_SLOTS,
            'bookedSlots' => $bookedSlots,
            'assessableStartups' => $assessableStartups,
            'selectedStartup' => $selectedStartup,
            'selectedStage' => $selectedStage,
            'currentAssessment' => $currentAssessment,
            'stageSummary' => $stageSummary,
            'activeDocuments' => $activeDocuments,
            'ventureExitDocument' => $ventureExitDocument,
            'postAssessmentSummary' => $postAssessmentSummary,
            'incompleteAssessments' => $incompleteAssessments,
            'savedReports' => $savedReports,
            'rubricMeta' => ReadinessRubric::meta($selectedStage),
            'rubricLevels' => ReadinessRubric::all(),
            'stages' => ReadinessRubric::STAGES,
            'overviewRows' => $overviewRows,
        ]);
    }
}
