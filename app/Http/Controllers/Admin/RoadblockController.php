<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignRoadblockRequest;
use App\Models\Mentor;
use App\Models\Roadblock;

class RoadblockController extends Controller
{
    public function index()
    {
        $pending = Roadblock::with(['startup', 'files'])
            ->where('status', 'Pending')
            ->latest()
            ->get();

        $scheduled = Roadblock::with(['startup', 'mentor'])
            ->where('status', 'Scheduled')
            ->get();

        $upcoming = $scheduled->reject->isInAssessment()->sortBy('meeting_date')->values();
        $scheduledToday = $upcoming->filter(fn ($r) => $r->meeting_date?->isToday())->values();
        $assessment = $scheduled->filter->isInAssessment()->sortByDesc('meeting_date')->values();

        $resolved = Roadblock::with(['startup', 'mentor'])
            ->where('status', 'Resolved')
            ->orderByDesc('resolved_at')
            ->get();

        $failed = Roadblock::with(['startup', 'mentor'])
            ->where('status', 'Failed')
            ->orderByDesc('failed_at')
            ->get();

        $mentors = Mentor::orderBy('mentor_id')->get();

        return view('admin.roadblocks.index', [
            'pending' => $pending,
            'upcoming' => $upcoming,
            'scheduledToday' => $scheduledToday,
            'assessment' => $assessment,
            'resolved' => $resolved,
            'failed' => $failed,
            'mentors' => $mentors,
        ]);
    }

    public function assign(AssignRoadblockRequest $request, Roadblock $roadblock)
    {
        $roadblock->update([
            ...$request->validated(),
            'status' => 'Scheduled',
            'resolved_at' => null,
            'failed_at' => null,
        ]);

        return back()->with('status', 'Roadblock scheduled.');
    }

    public function unassign(Roadblock $roadblock)
    {
        $roadblock->update([
            'mentor_id' => null,
            'meeting_date' => null,
            'meeting_start_time' => null,
            'meeting_end_time' => null,
            'meeting_platform' => null,
            'meeting_link' => null,
            'status' => 'Pending',
        ]);

        return back()->with('status', 'Assignment removed.');
    }

    public function resolve(Roadblock $roadblock)
    {
        $roadblock->update(['status' => 'Resolved', 'resolved_at' => now()]);

        return back()->with('status', 'Roadblock marked resolved.');
    }

    public function fail(Roadblock $roadblock)
    {
        $roadblock->update(['status' => 'Failed', 'failed_at' => now()]);

        return back()->with('status', 'Roadblock marked failed.');
    }

    public function recover(Roadblock $roadblock)
    {
        $roadblock->update(['status' => 'Scheduled', 'resolved_at' => null]);

        return back()->with('status', 'Roadblock recovered to assessment.');
    }

    public function destroy(Roadblock $roadblock)
    {
        $roadblock->delete();

        return back()->with('status', 'Roadblock deleted.');
    }
}
