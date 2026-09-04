<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCohortRequest;
use App\Http\Requests\Admin\UpdateCohortRequest;
use App\Models\Cohort;
use Illuminate\Http\RedirectResponse;

// No index() here — cohort management is now handled entirely through the
// admin Dashboard's cohort selector + 3-dot menu (see resources/views/dashboard.blade.php),
// not a standalone listing page.
class CohortController extends Controller
{
    public function store(StoreCohortRequest $request): RedirectResponse
    {
        Cohort::create([
            ...$request->validated(),
            // Not user-entered (see StoreCohortRequest) — auto-assigned so
            // Startup::cohort_number (used all over the rest of the app)
            // still has a real, unique integer to key off of.
            'number' => (Cohort::max('number') ?? 0) + 1,
            'status' => 'Active',
        ]);

        return redirect()->back()->with('cohortAction', 'created');
    }

    public function update(UpdateCohortRequest $request, Cohort $cohort): RedirectResponse
    {
        $data = $request->validated();

        // Archived cohorts are historical records — their start/end dates
        // are locked once archived, even if the request somehow includes
        // changed values (e.g. a stale form re-submitted after archiving).
        if ($cohort->isArchived()) {
            unset($data['start_date'], $data['end_date']);
        }

        $cohort->update($data);

        return redirect()->back()->with('cohortAction', 'updated');
    }

    /**
     * "Archive/End Cohort" — a distinct action from delete. Sets the
     * existing 'Inactive' status value (displayed as "Archived", see
     * Cohort::getStatusLabelAttribute()). Startups keep their cohort_id
     * untouched; only new assignment/assessment activity is expected to
     * stop, which is enforced at the point those actions happen, not here.
     */
    public function archive(Cohort $cohort): RedirectResponse
    {
        // A cohort can only go through the archiving process once — no-op
        // if it's already archived rather than re-processing it.
        if ($cohort->isArchived()) {
            return redirect()->back();
        }

        $cohort->update(['status' => 'Inactive']);

        return redirect()->back()->with('cohortAction', 'archived');
    }

    public function destroy(Cohort $cohort): RedirectResponse
    {
        // Startups already assigned to this cohort keep their cohort_id set
        // to null (see the nullOnDelete() FK) rather than being blocked or
        // cascaded — their cohort_number (used everywhere else in the app)
        // is untouched either way.
        $cohort->delete();

        return redirect()->back()->with('cohortAction', 'deleted');
    }
}
