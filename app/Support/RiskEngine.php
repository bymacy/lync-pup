<?php

namespace App\Support;

use App\Models\AssessmentDocument;
use App\Models\Startup;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Computes per-startup risk indicators for the admin Risk Monitoring module.
 *
 * Scoring model (per the reference spec): each indicator that is currently
 * "triggered" for a startup contributes its fixed base score plus a
 * time-based escalation (how long the underlying problem has gone
 * unaddressed). A startup's Total Risk Score is the SUM of every triggered
 * indicator's score, classified into an overall risk level.
 */
class RiskEngine
{
    public const CATEGORY_INFO_SHEET = 'Information Sheet';

    public const CATEGORY_COORDINATOR = 'Portfolio Coordinator';

    public const CATEGORY_MENTOR = 'Mentor Coordination';

    public const CATEGORY_ASSESSMENT = 'Readiness Assessment';

    public const CATEGORIES = [
        self::CATEGORY_INFO_SHEET,
        self::CATEGORY_COORDINATOR,
        self::CATEGORY_MENTOR,
        self::CATEGORY_ASSESSMENT,
    ];

    /**
     * Pre/Active/Post-Assessment (and Venture Exit) have no stored deadline
     * anywhere in the app — unlike the other indicators, which anchor off a
     * real timestamp already in the data (startup creation, sheet
     * submission, roadblock creation). Per the incubation team, these four
     * stages run on a fixed calendar relative to the startup's COHORT start
     * date, not the startup's own timeline: Pre-Assessment is due 2 months
     * after cohort start, Active-Assessment 4 months after, and both
     * Post-Assessment and Venture Exit 5 months after. A startup whose
     * cohort has no start_date set is never flagged for these — there's
     * nothing to measure against.
     */
    public const ASSESSMENT_DUE_MONTHS = [
        'no_pre_assessment' => 2,
        'no_active_assessment' => 4,
        'no_post_assessment' => 5,
        'no_venture_exit' => 5,
    ];

    /**
     * Indicator definitions. "Failed Mentorship" is the 6th indicator, added
     * on top of the original 5 from the reference doc (Severity: High, Base
     * Score: 4, confirmed). It carries no time-based escalation — unlike the
     * other indicators, a "Failed" roadblock status is a discrete terminal
     * outcome rather than an ongoing delay that worsens with time, so only
     * its flat base score applies.
     */
    public const INDICATORS = [
        'no_information_sheet' => [
            'label' => 'No Information Sheet',
            'category' => self::CATEGORY_INFO_SHEET,
            'severity' => 'Critical',
            'base_score' => 5,
        ],

        // See the "Incomplete vs Not Evaluated" docblock above assess()
        // for why these two are split on `submission_date` rather than
        // both just checking `approval_status !== 'Approved'`.
        'incomplete_information_sheet' => [
            'label' => 'Incomplete Information Sheet',
            'category' => self::CATEGORY_INFO_SHEET,
            'severity' => 'High',
            'base_score' => 4,
        ],
        'information_sheet_not_evaluated' => [
            'label' => 'Information Sheet Not Evaluated',
            'category' => self::CATEGORY_INFO_SHEET,
            'severity' => 'High',
            'base_score' => 4,
        ],
        'no_mentor_assigned' => [
            'label' => 'No Mentor Assigned to Submitted Roadblock',
            'category' => self::CATEGORY_MENTOR,
            'severity' => 'Medium',
            'base_score' => 3,
        ],
        'no_portfolio_coordinator' => [
            'label' => 'No Portfolio Coordinator Assigned',
            'category' => self::CATEGORY_COORDINATOR,
            'severity' => 'Low',
            'base_score' => 1,
        ],
        'failed_mentorship' => [
            'label' => 'Failed Mentorship',
            'category' => self::CATEGORY_MENTOR,
            'severity' => 'High',
            'base_score' => 4,
        ],

        // See ASSESSMENT_DUE_MONTHS docblock above for why these four are
        // measured against the cohort's start date rather than the
        // startup's own timeline. Severity increases the closer to
        // graduation the missed stage is, since less runway remains to
        // recover from it.
        'no_pre_assessment' => [
            'label' => 'Pre-Assessment Overdue',
            'category' => self::CATEGORY_ASSESSMENT,
            'severity' => 'High',
            'base_score' => 4,
        ],
        'no_active_assessment' => [
            'label' => 'Active-Assessment Overdue',
            'category' => self::CATEGORY_ASSESSMENT,
            'severity' => 'High',
            'base_score' => 4,
        ],
        'no_post_assessment' => [
            'label' => 'Post-Assessment Overdue',
            'category' => self::CATEGORY_ASSESSMENT,
            'severity' => 'Critical',
            'base_score' => 5,
        ],
        'no_venture_exit' => [
            'label' => 'Venture Exit Overdue',
            'category' => self::CATEGORY_ASSESSMENT,
            'severity' => 'Medium',
            'base_score' => 3,
        ],
    ];

    /** Overall Total Risk Score -> risk level, per the reference thresholds. */
    public const LEVEL_COLORS = [
        'Critical' => '#FF2525',
        'High' => '#FF9B20',
        'Moderate' => '#FFE120',
        'Low' => '#00BF1D',
        'None' => '#9CA3AF',
    ];

    /** Per-indicator severity -> color, kept separate from LEVEL_COLORS since
     *  indicator severities use "Medium" where overall levels use "Moderate". */
    public const SEVERITY_COLORS = [
        'Critical' => '#FF2525',
        'High' => '#FF9B20',
        'Medium' => '#FFE120',
        'Low' => '#00BF1D',
    ];

    public static function classify(int $score): string
    {
        return match (true) {
            $score >= 15 => 'Critical',
            $score >= 10 => 'High',
            $score >= 5 => 'Moderate',
            $score >= 1 => 'Low',
            default => 'None',
        };
    }

    /**
     * Assess a single startup. $startup must have `informationSheet`,
     * `activeCoordinatorAssignment`, `roadblocks`, `readinessAssessments`,
     * and `cohort` eager-loaded by the caller to avoid N+1 queries.
     * $documents is that startup's full AssessmentDocument collection (any
     * stage/number) — used to read Docs 6/7/8 (Active-Assessment
     * completion) and the Venture Exit form.
     */
    public static function assess(Startup $startup, ?Collection $documents = null): array
    {
        $documents = $documents ?? collect();
        $triggered = [];

        $infoSheet = $startup->informationSheet;
        $isApproved = $infoSheet && $infoSheet->approval_status === 'Approved';

        // 1. No Information Sheet — clock starts when the startup itself was
        // created, since there's no sheet submission to anchor to yet.
        if (! $infoSheet) {
            $triggered['no_information_sheet'] = self::dayTierScore(
                $startup->created_at ? Carbon::parse($startup->created_at) : null
            );
        }

        // 2 & 3. Incomplete vs Not Evaluated — an InformationSheet row can
        // exist without the founder ever having gone through the actual
        // Information Sheet submission flow: the Startup Profile page
        // (app/Http/Controllers/Startup/StartupProfileController.php)
        // also creates/touches this row (setting only business_description)
        // whenever a founder saves their profile, well before they've
        // filled in or submitted the real Information Sheet form. The one
        // reliable signal that the actual form was submitted is
        // `submission_date`, which is only ever set inside
        // InformationSheetController::update() — never by the profile save.
        // So: a row with no submission_date is "started but not finished"
        // (Incomplete); a row with a submission_date but not yet Approved
        // is genuinely "submitted, awaiting admin evaluation" (Not
        // Evaluated).
        if ($infoSheet && ! $infoSheet->submission_date) {
            $triggered['incomplete_information_sheet'] = self::dayTierScore(
                $infoSheet->created_at ? Carbon::parse($infoSheet->created_at) : null
            );
        }

        if ($infoSheet && $infoSheet->submission_date && $infoSheet->approval_status !== 'Approved') {
            $triggered['information_sheet_not_evaluated'] = self::dayTierScore(
                Carbon::parse($infoSheet->submission_date)
            );
        }

        // 4. No Mentor Assigned to Submitted Roadblock — a roadblock still
        // sitting in 'Pending' (not yet Scheduled/Resolved/Failed) with no
        // mentor_id means it's awaiting assignment. Use the oldest such
        // roadblock so the score reflects the longest-ignored case.
        $unassigned = $startup->roadblocks
            ->where('status', 'Pending')
            ->whereNull('mentor_id')
            ->sortBy('created_at')
            ->first();
        if ($unassigned) {
            $triggered['no_mentor_assigned'] = self::dayTierScore(Carbon::parse($unassigned->created_at));
        }

        // 5. No Portfolio Coordinator Assigned — only meaningful once a
        // startup is actually in the program (info sheet approved),
        // otherwise every not-yet-approved applicant would spuriously show
        // this risk from day one. There's no dedicated "approved_at"
        // timestamp on InformationSheet, so its updated_at is used as the
        // best-available proxy for when the startup became active.
        if ($isApproved && ! $startup->activeCoordinatorAssignment) {
            $triggered['no_portfolio_coordinator'] = self::dayTierScore(
                Carbon::parse($infoSheet->updated_at ?? $startup->created_at)
            );
        }

        // 6. Failed Mentorship — flat score, see class docblock.
        if ($startup->roadblocks->contains(fn ($roadblock) => $roadblock->status === 'Failed')) {
            $triggered['failed_mentorship'] = 0;
        }

        // 7-10. Pre/Active/Post-Assessment + Venture Exit — see
        // ASSESSMENT_DUE_MONTHS docblock for why these are measured against
        // the startup's COHORT start date instead of its own timeline. No
        // cohort / no start_date set means there's nothing to measure
        // against, so these simply never trigger for that startup.
        $cohortStart = $startup->cohort?->start_date ? Carbon::parse($startup->cohort->start_date) : null;
        if ($cohortStart) {
            $hasPreAssessment = $startup->readinessAssessments->contains(
                fn ($a) => $a->stage === 'Pre-Assessment' && $a->overall_score !== null
            );
            if (! $hasPreAssessment) {
                $score = self::assessmentDueScore($cohortStart, self::ASSESSMENT_DUE_MONTHS['no_pre_assessment']);
                if ($score !== null) {
                    $triggered['no_pre_assessment'] = $score;
                }
            }

            $activeDocsCount = $documents
                ->where('stage', 'Active-Assessment')
                ->whereIn('document_number', [6, 7, 8])
                ->pluck('document_number')
                ->unique()
                ->count();
            if ($activeDocsCount < 3) {
                $score = self::assessmentDueScore($cohortStart, self::ASSESSMENT_DUE_MONTHS['no_active_assessment']);
                if ($score !== null) {
                    $triggered['no_active_assessment'] = $score;
                }
            }

            $hasPostAssessment = $startup->readinessAssessments->contains(
                fn ($a) => $a->stage === 'Post-Assessment' && $a->overall_score !== null
            );
            if (! $hasPostAssessment) {
                $score = self::assessmentDueScore($cohortStart, self::ASSESSMENT_DUE_MONTHS['no_post_assessment']);
                if ($score !== null) {
                    $triggered['no_post_assessment'] = $score;
                }
            }

            $hasVentureExit = $documents->contains(
                fn (AssessmentDocument $d) => $d->document_number === VentureExitForm::DOCUMENT_NUMBER
            );
            if (! $hasVentureExit) {
                $score = self::assessmentDueScore($cohortStart, self::ASSESSMENT_DUE_MONTHS['no_venture_exit']);
                if ($score !== null) {
                    $triggered['no_venture_exit'] = $score;
                }
            }
        }

        $total = 0;
        $indicators = [];
        foreach ($triggered as $key => $additional) {
            $meta = self::INDICATORS[$key];
            $score = $meta['base_score'] + $additional;
            $total += $score;
            $indicators[] = [
                'key' => $key,
                'label' => $meta['label'],
                'category' => $meta['category'],
                'severity' => $meta['severity'],
                'base_score' => $meta['base_score'],
                'additional_score' => $additional,
                'score' => $score,
                'link' => self::resolveLink($key, $startup),
            ];
        }

        return [
            'score' => $total,
            'level' => self::classify($total),
            'indicators' => $indicators,
        ];
    }

    /**
     * Where an admin should go to actually resolve a triggered indicator —
     * "the causes of the problem," not just a description of it. Each
     * indicator type routes to whichever existing admin page/section
     * already handles that problem:
     *  - Information Sheet indicators -> that startup's Information Sheet
     *    detail/evaluation page (works even if no sheet exists yet).
     *  - Mentor/roadblock indicators -> Roadblock Management, landing on
     *    the Manage or Archive/Failed tab as appropriate, with that
     *    startup's card/row flash-highlighted (see resources/js/app.js's
     *    flashHighlightFromQuery + the data-highlight-id="startup-{id}"
     *    attributes on those rows).
     *  - No Portfolio Coordinator -> that startup's profile page, flashing
     *    the existing "Portfolio Coordinator" section.
     *  - Readiness Assessment indicators -> Assessment Hub's Assessment
     *    tab, already scoped to that exact startup + stage — no highlight
     *    needed since the page itself is the resolution destination.
     */
    protected static function resolveLink(string $key, Startup $startup): ?string
    {
        return match ($key) {
            'no_information_sheet', 'incomplete_information_sheet', 'information_sheet_not_evaluated' =>
                route('admin.information-sheet.show', $startup),

            'no_mentor_assigned' => route('admin.roadblocks.index', [
                'tab' => 'manage',
                'highlight' => 'startup-'.$startup->startup_id,
            ]),
            'failed_mentorship' => route('admin.roadblocks.index', [
                'tab' => 'archive',
                'stage' => 'failed',
                'highlight' => 'startup-'.$startup->startup_id,
            ]),

            'no_portfolio_coordinator' => route('admin.startups.show', [
                'startup' => $startup,
                'highlight' => 'coordinator',
            ]),

            'no_pre_assessment' => self::assessmentHubLink($startup, 'Pre-Assessment'),
            'no_active_assessment' => self::assessmentHubLink($startup, 'Active-Assessment'),
            'no_post_assessment' => self::assessmentHubLink($startup, 'Post-Assessment'),
            'no_venture_exit' => self::assessmentHubLink($startup, 'Venture Exit'),

            default => null,
        };
    }

    protected static function assessmentHubLink(Startup $startup, string $stage): string
    {
        return route('admin.assessment-hub.index', [
            'main' => 'assessment',
            'stage' => $stage,
            'assessment_startup' => $startup->startup_id,
        ]);
    }

    /**
     * Deadline-based version of dayTierScore(): $cohortStart + $dueMonths is
     * the due date. Returns null (don't trigger) if that date hasn't passed
     * yet, otherwise the same 1-3/4-7/8+ day-late tiers as everything else.
     */
    protected static function assessmentDueScore(Carbon $cohortStart, int $dueMonths): ?int
    {
        $dueDate = $cohortStart->copy()->addMonths($dueMonths);

        if (now()->lessThan($dueDate)) {
            return null;
        }

        return self::dayTierScore($dueDate);
    }

    /** Tiers: 1-3 days = +1, 4-7 days = +2, 8+ days = +3. */
    protected static function dayTierScore(?Carbon $since): int
    {
        if (! $since) {
            return 0;
        }

        $days = $since->diffInDays(now());

        return match (true) {
            $days >= 8 => 3,
            $days >= 4 => 2,
            $days >= 1 => 1,
            default => 0,
        };
    }
}
