<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveFounderApplicationRequest;
use App\Http\Requests\Admin\RejectFounderApplicationRequest;
use App\Mail\FounderApplicationApproved;
use App\Mail\FounderApplicationRejected;
use App\Models\Cohort;
use App\Models\Coordinator;
use App\Models\CoordinatorAssignment;
use App\Models\Startup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class FounderApplicationController extends Controller
{
    private const PER_PAGE_OPTIONS = [4, 10, 20, 50];

    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'all');
        $perPage = (int) $request->query('per_page', 10);
        $cohortId = $request->query('cohort');

        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = 10;
        }

        $query = Startup::query()
            ->whereHas('user', fn ($q) => $q->where('role', 'Startup'))
            ->with(['user', 'cohort', 'activeCoordinatorAssignment.coordinator']);

        $query = match ($tab) {
            'pending' => $query->applicationPending(),
            'approved' => $query->applicationApproved(),
            'rejected' => $query->applicationRejected(),
            default => $query,
        };

        // Pending/rejected applicants have no cohort_id yet (only set once
        // approve() runs below), so filtering by a specific cohort here
        // naturally narrows down to that cohort's approved founders.
        $query = $query->when($cohortId, fn ($q) => $q->where('cohort_id', $cohortId));

        $applications = $query->latest()->paginate($perPage)->withQueryString();

        // Scoped counts first, then total = their sum, so the "Total
        // Application" card can never drift out of sync with the three tab
        // counts underneath it (rather than running a 4th, separately
        // scoped count() query that could disagree with the others on edge
        // cases, e.g. a Startup row whose user isn't role=Startup).
        $pendingCount = Startup::applicationPending()->count();
        $approvedCount = Startup::applicationApproved()->count();
        $rejectedCount = Startup::applicationRejected()->count();

        return view('admin.founder-applications.index', [
            'applications' => $applications,
            'activeTab' => $tab,
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'selectedCohortId' => $cohortId ? (int) $cohortId : null,
            // All cohorts (Active + Archived) for the page-level filter
            // dropdown — separate from the Active-only $cohorts below,
            // which is deliberately kept Active-only since it feeds the
            // Approve modal's "assign to cohort" select.
            'filterCohorts' => Cohort::orderByRaw("CASE WHEN status = 'Active' THEN 0 ELSE 1 END")
                ->orderBy('number')
                ->get(),
            'totals' => [
                'total' => $pendingCount + $approvedCount + $rejectedCount,
                'pending' => $pendingCount,
                'approved' => $approvedCount,
                'rejected' => $rejectedCount,
            ],
            'cohorts' => Cohort::where('status', 'Active')->orderBy('number')->get(),
            'coordinators' => Coordinator::orderBy('coordinator_id')->get(),
            // Same "Total X" breakdown pattern as the Startup Profile page's
            // cohortBreakdown: groups on cohort_number (populated once an
            // application is approved, see approve() above), scoped to the
            // same Startup+role='Startup' set the "Total Application" count
            // itself uses, so a pending/rejected applicant (no cohort yet)
            // simply doesn't show up in the breakdown.
            'cohortBreakdown' => Startup::query()
                ->whereHas('user', fn ($q) => $q->where('role', 'Startup'))
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

    public function approve(Startup $startup, ApproveFounderApplicationRequest $request): RedirectResponse
    {
        // Redirect gracefully instead of a bare 404 if this application was
        // already decided — e.g. a double-submitted form, or the admin
        // resubmitting a stale/cached "Pending" page after already acting
        // on it (via the browser back button, another tab, etc).
        if (! $startup->user?->isPendingApproval()) {
            return redirect()
                ->route('admin.founder-applications.index', $request->only('tab', 'per_page', 'cohort'))
                ->with('error', 'This application has already been processed.');
        }

        $cohort = Cohort::findOrFail($request->validated('cohort_id'));

        $startup->update([
            'cohort_id' => $cohort->cohort_id,
            // Kept in sync so every existing "Cohort {{ $startup->cohort_number }}"
            // display elsewhere in the app (dashboard, profile, roadblocks, etc.)
            // continues to work without changes.
            'cohort_number' => $cohort->number,
            'admin_remarks' => $request->validated('admin_remarks'),
            'application_decided_at' => now(),
        ]);

        $startup->user->update(['account_status' => 'Active']);

        if ($request->filled('coordinator_id')) {
            CoordinatorAssignment::create([
                'startup_id' => $startup->startup_id,
                'coordinator_id' => $request->validated('coordinator_id'),
                'assigned_date' => now(),
                'assignment_status' => 'Active',
            ]);
        }

        Mail::to($startup->user->email)->send(new FounderApplicationApproved($startup));

        return redirect()
            ->route('admin.founder-applications.index', $request->only('tab', 'per_page', 'cohort'))
            ->with('application_result', ['type' => 'approved', 'startup' => $startup->company_name]);
    }

    public function reject(Startup $startup, RejectFounderApplicationRequest $request): RedirectResponse
    {
        // Same graceful-redirect reasoning as approve() above.
        if (! $startup->user?->isPendingApproval()) {
            return redirect()
                ->route('admin.founder-applications.index', $request->only('tab', 'per_page', 'cohort'))
                ->with('error', 'This application has already been processed.');
        }

        $startup->update([
            'rejection_reason' => $request->validated('rejection_reason'),
            'admin_remarks' => $request->validated('admin_remarks'),
            'application_decided_at' => now(),
        ]);

        $startup->user->update(['account_status' => 'Rejected']);

        Mail::to($startup->user->email)->send(new FounderApplicationRejected($startup));

        return redirect()
            ->route('admin.founder-applications.index', $request->only('tab', 'per_page', 'cohort'))
            ->with('application_result', ['type' => 'rejected', 'startup' => $startup->company_name]);
    }

    /**
     * Permanently removes a Founder signup — both the Startup row and its
     * User row together, so this never leaves an orphaned account behind
     * (see the CleanOrphanedFounderAccounts command, written to mop up
     * exactly that kind of leftover from a manual/direct-DB delete done
     * outside the app).
     *
     * Deliberately scoped to still-Pending applications only. Once an
     * admin has approved or rejected someone, real activity (assessments,
     * roadblocks, uploaded files, etc.) may already hang off their
     * account, and a full cascading delete of all of that is a much
     * bigger, riskier feature than what this is for: letting an admin
     * clean up a junk/test/abandoned signup before it's ever been acted
     * on.
     */
    public function destroy(Startup $startup, Request $request): RedirectResponse
    {
        abort_unless($startup->user?->isPendingApproval(), 404);

        $user = $startup->user;
        $startup->delete();
        $user->delete();

        return redirect()
            ->route('admin.founder-applications.index', $request->only('tab', 'per_page', 'cohort'))
            ->with('status', 'Application deleted.');
    }
}
