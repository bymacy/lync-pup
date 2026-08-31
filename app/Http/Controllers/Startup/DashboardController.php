<?php

namespace App\Http\Controllers\Startup;

use App\Http\Controllers\Controller;
use App\Models\AssessmentDocument;
use App\Models\ReadinessLevelAssessment;
use App\Models\Startup;
use App\Support\ReadinessRubric;
use App\Support\VentureExitForm;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $startup = auth()->user()->startup;

        if (! $startup) {
            return view('startup.dashboard', ['startup' => null]);
        }

        $startup->loadMissing(['informationSheet', 'activeCoordinatorAssignment']);

        // "Cohort 3 - 001": no per-cohort sequence exists anywhere in the
        // app yet, so it's derived here — this startup's rank by startup_id
        // among every startup sharing the same cohort_number.
        $cohortSequence = Startup::where('cohort_number', $startup->cohort_number)
            ->where('startup_id', '<=', $startup->startup_id)
            ->count();

        // Prefer Post-Assessment once it exists (it supersedes Pre), else
        // fall back to Pre-Assessment, else there's simply nothing to show.
        $assessment = ReadinessLevelAssessment::where('startup_id', $startup->startup_id)
            ->where('stage', 'Post-Assessment')
            ->first();
        $readinessStage = $assessment ? 'Post-Assessment' : null;

        if (! $assessment) {
            $assessment = ReadinessLevelAssessment::where('startup_id', $startup->startup_id)
                ->where('stage', 'Pre-Assessment')
                ->first();
            $readinessStage = $assessment ? 'Pre-Assessment' : null;
        }

        return view('startup.dashboard', [
            'startup' => $startup,
            'cohortSequence' => $cohortSequence,
            'assessment' => $assessment,
            'readinessStage' => $readinessStage,
            'overallLabel' => ReadinessRubric::overallLabel($assessment->overall_score ?? null),
            'needsProfileSetup' => ! $startup->isProfileComplete(),
            'needsInformationSheet' => $startup->isProfileComplete() && ! $startup->hasSubmittedInformationSheet(),
            // Stage 2 -> 3 waiting room: the sheet is in, the remaining
            // modules are still locked (see EnsureFounderStage), so the
            // dashboard says why rather than leaving the founder guessing.
            'awaitingSheetApproval' => $startup->isProfileComplete()
                && $startup->hasSubmittedInformationSheet()
                && ! $startup->hasApprovedInformationSheet(),
            'onboardingSteps' => $this->onboardingSteps($startup),
            'graduationSteps' => $this->graduationSteps($startup),
            'updates' => $this->updates(),
        ]);
    }

    /**
     * The dashboard's "what's new" cards: unread notifications written at the
     * moment an admin actually changed something (see App\Notifications\*),
     * rather than conditions re-derived on every page load. Opening a card
     * marks that row read, so each one clears itself once it has been seen.
     *
     * Capped at three so a founder returning after a long absence gets a
     * readable dashboard rather than a wall of cards.
     */
    protected function updates(): array
    {
        return auth()->user()
            ->unreadNotifications()
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn ($note) => [
                'id' => $note->id,
                'title' => $note->data['title'] ?? 'Update',
                'body' => $note->data['body'] ?? '',
                'action' => $note->data['action'] ?? 'View',
                'icon' => $note->data['icon'] ?? 'info-sheet.svg',
            ])
            ->all();
    }

    /**
     * "Admin Review" has no dedicated status of its own anywhere in the data
     * model (InformationSheet.approval_status is only Pending/Approved/
     * Rejected) — a scheduled evaluation is used as the concrete signal
     * that review has actually happened, since admins only get there after
     * looking at the sheet.
     */
    protected function onboardingSteps(Startup $startup): array
    {
        $sheet = $startup->informationSheet;
        $approved = $sheet && $sheet->approval_status === 'Approved';

        return $this->stepsWithState([
            'Setup Startup Profile' => $startup->isProfileComplete(),
            'Completed Information Sheet' => $startup->hasSubmittedInformationSheet(),
            'Admin Review' => $startup->hasScheduledEvaluation() || $approved,
            'Schedule for Evaluation' => $startup->hasScheduledEvaluation(),
            'Approved' => $approved,
        ]);
    }

    /**
     * Mirrors how the admin Assessment Hub itself derives progress — there
     * is no "current stage" field on Startup anywhere; it's entirely
     * presence-of-rows-driven (ReadinessLevelAssessment per stage,
     * AssessmentDocument per stage+document_number).
     */
    protected function graduationSteps(Startup $startup): array
    {
        $preRl = ReadinessLevelAssessment::where('startup_id', $startup->startup_id)
            ->where('stage', 'Pre-Assessment')->first();
        $postRl = ReadinessLevelAssessment::where('startup_id', $startup->startup_id)
            ->where('stage', 'Post-Assessment')->first();
        $activeDocsCount = AssessmentDocument::where('startup_id', $startup->startup_id)
            ->where('stage', 'Active-Assessment')
            ->whereIn('document_number', [6, 7, 8])
            ->count();
        $ventureExitSubmitted = AssessmentDocument::where('startup_id', $startup->startup_id)
            ->where('stage', 'Venture Exit')
            ->where('document_number', VentureExitForm::DOCUMENT_NUMBER)
            ->exists();

        return $this->stepsWithState([
            'Active Startup' => $startup->status === 'Active',
            'Pre RL Documents' => $preRl && $preRl->overall_score !== null,
            'Active Documents' => $activeDocsCount >= 3,
            'Post RL Documents' => $postRl && $postRl->overall_score !== null,
            'Venture Exit' => $ventureExitSubmitted,
        ]);
    }

    /**
     * Turns an ordered [label => isDone] map into the step list the
     * <x-step-tracker> component expects: every step up to the first
     * incomplete one is "done", that first incomplete one is "current",
     * everything after is "upcoming" — forced, even if that later step's
     * own isDone happens to be true. Real milestones (a scheduled
     * evaluation, a submitted RL document, etc.) can exist independently
     * of each other in the data, but the tracker is a linear narrative, so
     * once an earlier step is incomplete every later step displays as not
     * yet reached rather than jumping ahead.
     */
    protected function stepsWithState(array $doneMap): array
    {
        $currentFound = false;
        $steps = [];
        $number = 0;

        foreach ($doneMap as $label => $isDone) {
            $number++;

            if ($isDone && ! $currentFound) {
                $state = 'done';
            } elseif (! $currentFound) {
                $state = 'current';
                $currentFound = true;
            } else {
                $state = 'upcoming';
            }

            $steps[] = ['label' => $label, 'state' => $state, 'number' => $number];
        }

        return $steps;
    }
}
