<?php

namespace App\Http\Controllers\Startup;

use App\Http\Controllers\Controller;
use App\Models\EvaluationSchedule;
use App\Models\Roadblock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MeetingController extends Controller
{
    public function index(): View
    {
        Roadblock::promoteEndedMeetingsToPendingReview();

        $startup = Auth::user()->startup;

        $mentorships = Roadblock::with('mentor')
            ->where('startup_id', $startup->startup_id)
            ->where('status', 'Scheduled')
            ->whereNotNull('meeting_date')
            ->get()
            ->reject->isInAssessment()
            ->map(function (Roadblock $roadblock) {
                return [
                    'type' => 'mentorship',
                    'sort_key' => $roadblock->meeting_date->format('Y-m-d').' '.$roadblock->meeting_start_time,
                    'date_label' => $roadblock->meeting_date->format('l, F j, Y'),
                    'time_label' => Carbon::parse($roadblock->meeting_start_time)->format('g:i A')
                        .' - '.Carbon::parse($roadblock->meeting_end_time)->format('g:i A'),
                    'status_label' => $this->dayLabel($roadblock->meeting_date),
                    'roadblock_category' => $roadblock->display_category,
                    'mentor_name' => trim(($roadblock->mentor->honorific ?? '').' '.($roadblock->mentor->last_name ?? '')) ?: '—',
                    'platform' => $roadblock->meeting_platform,
                    'meeting_link' => $roadblock->meeting_link,
                    'can_join' => $roadblock->isLive(),
                ];
            });

        $evaluations = EvaluationSchedule::where('startup_id', $startup->startup_id)
            ->where('status', 'Scheduled')
            ->get()
            ->reject->isMissed()
            ->map(function (EvaluationSchedule $schedule) {
                return [
                    'type' => 'evaluation',
                    'sort_key' => $schedule->evaluation_date->format('Y-m-d').' '.$schedule->start_time,
                    'date_label' => $schedule->evaluation_date->format('l, F j, Y'),
                    'time_label' => $schedule->time_range_label,
                    'status_label' => $this->dayLabel($schedule->evaluation_date),
                ];
            });

        $meetings = $mentorships->concat($evaluations)->sortBy('sort_key')->values();

        return view('startup.meetings.index', compact('meetings'));
    }

    private function dayLabel(Carbon $date): string
    {
        if ($date->isToday()) {
            return 'Today';
        }

        if ($date->isTomorrow()) {
            return 'Tomorrow';
        }

        return 'Upcoming';
    }
}
