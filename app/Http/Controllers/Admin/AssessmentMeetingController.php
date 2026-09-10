<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAssessmentMeetingRequest;
use App\Http\Requests\Admin\UpdateAssessmentMeetingRequest;
use App\Models\AssessmentMeeting;
use Illuminate\Http\RedirectResponse;

/**
 * The Assessment Hub's "Meetings" sub-nav (Today/Upcoming/Archive) — see
 * AssessmentMeeting's own class doc for what these are and how they differ
 * from the Information Sheet's EvaluationSchedule.
 */
class AssessmentMeetingController extends Controller
{
    public function store(StoreAssessmentMeetingRequest $request): RedirectResponse
    {
        AssessmentMeeting::create($request->validated());

        return redirect()
            ->route('admin.assessment-hub.index', ['main' => 'assessment', 'stage' => 'Meetings'])
            ->with('status', 'Meeting scheduled.');
    }

    public function update(UpdateAssessmentMeetingRequest $request, AssessmentMeeting $assessmentMeeting): RedirectResponse
    {
        $assessmentMeeting->update($request->validated());

        return redirect()
            ->route('admin.assessment-hub.index', ['main' => 'assessment', 'stage' => 'Meetings'])
            ->with('status', 'Meeting rescheduled.');
    }

    /**
     * The Meetings sub-nav's own delete — a plain confirm, not the typed-
     * "DELETE" pattern used for Startup accounts elsewhere: this only ever
     * removes a stale calendar record, never a founder's account or data.
     */
    public function destroy(AssessmentMeeting $assessmentMeeting): RedirectResponse
    {
        $assessmentMeeting->delete();

        return redirect()
            ->route('admin.assessment-hub.index', ['main' => 'assessment', 'stage' => 'Meetings'])
            ->with('status', 'Meeting removed.');
    }
}
