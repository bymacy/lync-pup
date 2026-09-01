<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Startup;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Mail\PitchDeckRequested;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;

class StartupProfileController extends Controller
{
    public function index(Request $request): View
    {
        // "Startup Profile" tracks progress AFTER a founder's application has
        // been approved (Founder Application handles the Pending/Rejected
        // vetting stage) — so every tab, every stat, and the "Total Startup"
        // count here are scoped to applicationApproved() and never include
        // still-pending or rejected applicants.
        $query = Startup::query()
            ->applicationApproved()
            ->with(['informationSheet', 'activeCoordinatorAssignment.coordinator', 'evaluationSchedules']);

        $query = match ($request->query('tab', 'all')) {
            'active' => $query->active(),
            'assign-coordinator' => $query->needsCoordinator(),
            'pending' => $query->awaitingEvaluation(),
            'onboarding' => $query->onboarding(),
            default => $query,
        };

        $startups = $query->latest()->paginate(12)->withQueryString();

        $totalStartups = Startup::applicationApproved()->count();
        $activeStartups = Startup::applicationApproved()->active()->count();
        $needsCoordinatorStartups = Startup::applicationApproved()->needsCoordinator()->count();

        return view('admin.startups.index', [
            'startups' => $startups,
            'activeTab' => $request->query('tab', 'all'),
            'totals' => [
                'total' => $totalStartups,
                'active' => $activeStartups,
                'needsCoordinator' => $needsCoordinatorStartups,
                'pending' => Startup::applicationApproved()->awaitingEvaluation()->count(),
            ],
            // cohort_number (not the newer cohort_id -> cohorts table FK) is the
            // field actually populated on existing startups and used everywhere
            // else in the app (see Startup::getBatchLabelAttribute()), so the
            // breakdown groups on that rather than the Cohort relationship.
            // Scoped to applicationApproved() too, so the breakdown's own
            // total always matches the "Total Startup" card above it.
            'cohortBreakdown' => Startup::query()
                ->applicationApproved()
                ->whereNotNull('cohort_number')
                ->selectRaw('cohort_number, count(*) as total')
                ->groupBy('cohort_number')
                ->orderBy('cohort_number')
                ->get()
                ->map(fn ($row) => [
                    'count' => $row->total,
                    'label' => "Cohort {$row->cohort_number}",
                ]),
        ]);
    }

    public function show(Startup $startup): View
    {
        $startup->load([
            'user', 'informationSheet', 'teamMembers',
            'latestReadinessAssessment', 'activeCoordinatorAssignment.coordinator',
        ]);

        return view('admin.startups.show', compact('startup'));
    }

    public function requestPitchDeck(Startup $startup): RedirectResponse
    {
        Mail::to($startup->user->email)->send(new PitchDeckRequested($startup));

        $startup->update(['pitch_deck_requested_at' => now()]);

        return redirect()
            ->route('admin.startups.show', $startup)
            ->with('status', 'Pitch deck request sent to '.$startup->user->email.'.');
    }
}