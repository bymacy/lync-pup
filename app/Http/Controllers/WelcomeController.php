<?php

namespace App\Http\Controllers;

use App\Models\Cohort;
use App\Models\Startup;
use App\Support\ActiveAssessmentForms;
use App\Support\ReadinessRubric;
use App\Support\VentureExitForm;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WelcomeController extends Controller
{
    /**
     * Public marketing landing page — no auth required. Meant to be linked
     * to from (or embedded in) the public incubation website, not the app's
     * own internal navigation, so it deliberately does NOT redirect a
     * logged-in founder/admin away from it.
     */
    public function index(): View
    {
        // Only startups the program has actually accepted are shown
        // publicly — Onboarding/Pending/Rejected applicants stay internal.
        $startups = Startup::query()
            ->whereHas('informationSheet', fn ($q) => $q->where('approval_status', 'Approved'))
            ->with([
                'cohort',
                'user',
                'latestReadinessAssessment',
                'readinessAssessments',
                'startupTeamMembers',
            ])
            ->get();

        $startupIds = $startups->pluck('startup_id');

        // "Graduated" has no dedicated flag anywhere in the schema — the
        // closest real signal is a startup having actually gone through the
        // Venture Exit form (AssessmentDocument stage=Venture Exit, doc 13),
        // the same document ActiveAssessmentForms/VentureExitForm already
        // treat as that stage's terminal artifact elsewhere in the app.
        $graduatedStartupIds = \App\Models\AssessmentDocument::whereIn('startup_id', $startupIds)
            ->where('stage', 'Venture Exit')
            ->where('document_number', VentureExitForm::DOCUMENT_NUMBER)
            ->get()
            ->filter(fn ($doc) => ActiveAssessmentForms::isDocumentFilled($doc->document_number, $doc->data ?? []))
            ->pluck('startup_id');

        $stats = [
            'active_ventures' => $startups->count() - $graduatedStartupIds->count(),
            'sectors' => $startups->pluck('industry_sector')->filter()->unique()->count(),
            'graduated' => $graduatedStartupIds->count(),
        ];

        // Grouped by cohort for the tabbed showcase — only cohorts that
        // actually have a publicly-shown startup get a tab, ordered by
        // cohort number, Active cohorts before Archived ones.
        $cohorts = Cohort::whereIn('cohort_id', $startups->pluck('cohort_id')->filter()->unique())
            ->orderByRaw("CASE WHEN status = 'Active' THEN 0 ELSE 1 END")
            ->orderBy('number')
            ->get();

        $startupsByCohort = $startups->groupBy('cohort_id');

        $cohortShowcase = $cohorts->map(fn (Cohort $cohort) => [
            'cohort' => $cohort,
            'startups' => ($startupsByCohort->get($cohort->cohort_id) ?? collect())
                ->map(fn (Startup $startup) => $this->presentStartup($startup))
                ->values(),
        ])->filter(fn ($group) => $group['startups']->isNotEmpty())->values();

        return view('welcome', [
            'stats' => $stats,
            'cohortShowcase' => $cohortShowcase,
        ]);
    }

    /**
     * Shapes one startup's public data for both the card grid and the
     * detail modal — the modal gets everything (team, contact, every
     * assessed stage's scores), the card only reads a handful of these
     * keys, but building it once avoids two divergent representations.
     */
    protected function presentStartup(Startup $startup): array
    {
        // Same deterministic palette/icon pairing as the admin Startup
        // Profile cards (components/startup-card.blade.php), reused here so
        // a startup's color identity stays consistent across both the
        // public site and the internal app.
        $paletteIndex = $startup->startup_id % 4;

        $overallScore = $startup->latestReadinessAssessment?->overall_score;

        $stages = $startup->readinessAssessments
            ->mapWithKeys(fn ($assessment) => [
                $assessment->stage => [
                    'label' => $assessment->stage,
                    'scores' => collect(ReadinessRubric::TYPES)->mapWithKeys(fn ($type) => [
                        $type => $assessment->scoreFor($type),
                    ]),
                    'overall_score' => $assessment->overall_score,
                ],
            ]);

        return [
            'id' => $startup->startup_id,
            'name' => $startup->company_name,
            'sector' => $startup->industry_sector,
            'cohort_label' => $startup->cohort?->display_label ?? $startup->batch_label,
            'location' => $startup->location,
            'description' => $startup->business_description,
            'photo_url' => $startup->startup_photo_url,
            'palette_index' => $paletteIndex,
            'stage_label' => ReadinessRubric::overallLabel($overallScore),
            'overall_score' => $overallScore,
            'stages' => $stages,
            'default_stage' => $stages->keys()->first(),
            'team' => $startup->startupTeamMembers->pluck('full_name')->values(),
            'website' => $startup->website,
            'email' => $startup->user?->email,
            'phone' => $startup->contact_phone,
        ];
    }
}
