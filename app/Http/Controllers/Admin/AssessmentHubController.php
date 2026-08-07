<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluationSchedule;
use App\Models\Startup;
use Illuminate\View\View;

class AssessmentHubController extends Controller
{
    public function index(): View
    {
        $pendingStartups = Startup::with(['informationSheet', 'latestEvaluationSchedule'])
            ->pending()
            ->orderBy('created_at')
            ->get();

        $scheduledToday = EvaluationSchedule::with('startup')
            ->where('status', 'Scheduled')
            ->whereDate('evaluation_date', now()->toDateString())
            ->orderBy('start_time')
            ->get();

        $scheduled = EvaluationSchedule::with('startup')
            ->where('status', 'Scheduled')
            ->get();

        $todayEvaluations = $scheduled->filter->isToday()->sortBy('start_time')->values();
        $upcomingEvaluations = $scheduled->filter->isUpcoming()->sortBy(['evaluation_date', 'start_time'])->values();
        $missedEvaluations = $scheduled->filter->isMissed()->sortByDesc('evaluation_date')->values();

        $approvedStartups = Startup::with('informationSheet')
            ->whereHas('informationSheet', fn ($q) => $q->where('approval_status', 'Approved'))
            ->orderBy('company_name')
            ->get();

        $bookedSlots = $scheduled
            ->filter(fn ($row) => $row->evaluation_date->gte(now()->startOfDay()))
            ->groupBy(fn ($row) => $row->evaluation_date->format('Y-m-d'))
            ->map(fn ($rows) => $rows->map(fn ($row) => [
                'id' => $row->evaluation_schedule_id,
                'start_time' => substr($row->start_time, 0, 5),
            ])->values())
            ->toArray();

        return view('admin.assessment-hub.index', [
            'pendingStartups' => $pendingStartups,
            'scheduledToday' => $scheduledToday,
            'todayEvaluations' => $todayEvaluations,
            'upcomingEvaluations' => $upcomingEvaluations,
            'missedEvaluations' => $missedEvaluations,
            'approvedStartups' => $approvedStartups,
            'timeSlots' => EvaluationSchedule::TIME_SLOTS,
            'bookedSlots' => $bookedSlots,
        ]);
    }
}
