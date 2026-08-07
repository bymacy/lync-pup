<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEvaluationScheduleRequest;
use App\Http\Requests\Admin\UpdateEvaluationScheduleRequest;
use App\Models\EvaluationSchedule;
use Illuminate\Http\RedirectResponse;

class EvaluationScheduleController extends Controller
{
    public function store(StoreEvaluationScheduleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['end_time'] = $this->endTimeFor($data['start_time']);
        $data['status'] = 'Scheduled';

        EvaluationSchedule::create($data);

        return back()->with('status', 'Evaluation scheduled successfully.');
    }

    public function update(UpdateEvaluationScheduleRequest $request, EvaluationSchedule $evaluationSchedule): RedirectResponse
    {
        $data = $request->validated();
        $data['end_time'] = $this->endTimeFor($data['start_time']);
        $data['status'] = 'Scheduled';

        $evaluationSchedule->update($data);

        return back()->with('status', 'Evaluation schedule saved successfully.');
    }

    public function destroy(EvaluationSchedule $evaluationSchedule): RedirectResponse
    {
        $evaluationSchedule->delete();

        return back()->with('status', 'Evaluation schedule deleted.');
    }

    private function endTimeFor(string $startTime): string
    {
        foreach (EvaluationSchedule::TIME_SLOTS as [$start, $end]) {
            if ($start === $startTime) {
                return $end;
            }
        }

        abort(422, 'Invalid time slot.');
    }
}
