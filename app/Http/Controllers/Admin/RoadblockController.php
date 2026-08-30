<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignRoadblockRequest;
use App\Models\Coordinator;
use App\Models\Mentor;
use App\Models\Roadblock;
use App\Notifications\MentorshipScheduled;
use App\Notifications\RoadblockStatusUpdated;

class RoadblockController extends Controller
{
    public function index()
    {
        Roadblock::promoteEndedMeetingsToPendingReview();

        $pending = Roadblock::with(['startup', 'files'])
            ->where('status', 'Pending')
            ->latest()
            ->get();

        // Pull both statuses here: Scheduled rows that haven't concluded yet
        // (upcoming) plus anything already promoted to Pending Review. The
        // isInAssessment() split below still catches the rare in-between row
        // whose meeting just ended but hasn't been swept yet.
        $scheduled = Roadblock::with(['startup', 'mentor', 'coordinator'])
            ->whereIn('status', ['Scheduled', 'Pending Review'])
            ->get();

        // sortBy('meeting_date') only compares the date part, so multiple
        // meetings on the same day kept whatever arbitrary order the query
        // happened to return them in — testers correctly flagged this as
        // not actually ordered by time. Sort by the full start timestamp
        // instead, and pull anything currently Live to the very top
        // regardless of what time it started, per the requested priority.
        $upcoming = $scheduled->reject->isInAssessment()
            ->sort(function (Roadblock $a, Roadblock $b) {
                $liveRank = ($b->isLive() ? 1 : 0) - ($a->isLive() ? 1 : 0);

                return $liveRank !== 0 ? $liveRank : $a->meeting_starts_at <=> $b->meeting_starts_at;
            })
            ->values();
        $scheduledToday = $upcoming->filter(fn ($r) => $r->meeting_date?->isToday())->values();
        $assessment = $scheduled->filter->isInAssessment()->sortByDesc('meeting_date')->values();

        $resolved = Roadblock::with(['startup', 'mentor', 'coordinator'])
            ->where('status', 'Resolved')
            ->orderByDesc('resolved_at')
            ->get();

        $failed = Roadblock::with(['startup', 'mentor', 'coordinator'])
            ->where('status', 'Failed')
            ->orderByDesc('failed_at')
            ->get();

        $mentors = Mentor::orderBy('mentor_id')->get();
        $coordinators = Coordinator::orderBy('coordinator_id')->get();

        return view('admin.roadblocks.index', [
            'pending' => $pending,
            'upcoming' => $upcoming,
            'scheduledToday' => $scheduledToday,
            'assessment' => $assessment,
            'resolved' => $resolved,
            'failed' => $failed,
            'mentors' => $mentors,
            'coordinators' => $coordinators,
        ]);
    }

    public function assign(AssignRoadblockRequest $request, Roadblock $roadblock)
    {
        if ($roadblock->status === 'Resolved') {
            return back()->with('error', 'This roadblock is already resolved. Recover it first before reassigning.');
        }

        $validated = $request->validated();

        $roadblock->update([
            ...$validated,
            // Explicitly set both every time (defaulting to null if absent from
            // $validated) so switching a roadblock from a mentor to a coordinator
            // (or back) always clears out whichever one is no longer assigned,
            // instead of leaving a stale id behind on the other column.
            'mentor_id' => $validated['mentor_id'] ?? null,
            'coordinator_id' => $validated['coordinator_id'] ?? null,
            'status' => 'Scheduled',
            'resolved_at' => null,
            'failed_at' => null,
        ]);

        // Tells the founder a mentor and a slot now exist. Fired on every
        // assign, including a reassignment to a different mentor or a moved
        // meeting — from the founder's side those are all "your session
        // changed, go look", which is exactly what needs announcing.
        $roadblock->startup?->user?->notify(new MentorshipScheduled($roadblock->fresh(['mentor', 'coordinator'])));

        return back()->with('status', 'Roadblock scheduled.');
    }

    public function unassign(Roadblock $roadblock)
    {
        if ($roadblock->status !== 'Scheduled') {
            return back()->with('error', 'Only a scheduled roadblock can be deleted this way.');
        }

        // "Delete Assignment" on an already-scheduled roadblock permanently
        // deletes the roadblock/submission itself — it does NOT send it back
        // to the Pending list. (It used to reset it back to Pending, but
        // testers found that confusing: a deleted mentorship reappearing in
        // Pending looked like it hadn't actually been deleted.)
        $roadblock->delete();

        return back()->with('status', 'Roadblock deleted.');
    }

    public function resolve(Roadblock $roadblock)
    {
        if (! $roadblock->isInAssessment()) {
            return back()->with('error', 'This roadblock can only be resolved once its meeting has taken place.');
        }

        $roadblock->update(['status' => 'Resolved', 'resolved_at' => now()]);

        $roadblock->startup?->user?->notify(new RoadblockStatusUpdated($roadblock, 'Resolved'));

        // Jump straight to the Resolved stage so the admin lands where the
        // roadblock actually went, instead of staying on Pending Review where
        // it no longer appears.
        return redirect()->route('admin.roadblocks.index', ['tab' => 'archive', 'stage' => 'resolved'])
            ->with('status', 'Roadblock marked resolved.');
    }

    public function fail(Roadblock $roadblock)
    {
        if (! $roadblock->isInAssessment()) {
            return back()->with('error', 'This roadblock can only be marked failed once its meeting has taken place.');
        }

        $roadblock->update(['status' => 'Failed', 'failed_at' => now()]);

        $roadblock->startup?->user?->notify(new RoadblockStatusUpdated($roadblock, 'Failed'));

        return redirect()->route('admin.roadblocks.index', ['tab' => 'archive', 'stage' => 'failed'])
            ->with('status', 'Roadblock marked failed.');
    }

    public function recover(Roadblock $roadblock)
    {
        if ($roadblock->status !== 'Resolved') {
            return back()->with('error', 'Only a resolved roadblock can be recovered.');
        }

        // Back to Pending Review, not Scheduled — the meeting already happened,
        // so this goes straight back to awaiting a Resolved/Failed decision.
        $roadblock->update(['status' => 'Pending Review', 'resolved_at' => null]);

        $roadblock->startup?->user?->notify(new RoadblockStatusUpdated($roadblock, 'Pending Review'));

        return redirect()->route('admin.roadblocks.index', ['tab' => 'archive', 'stage' => 'assessment'])
            ->with('status', 'Roadblock recovered to Pending Review.');
    }

    public function destroy(Roadblock $roadblock)
    {
        $roadblock->delete();

        return back()->with('status', 'Roadblock deleted.');
    }
}
