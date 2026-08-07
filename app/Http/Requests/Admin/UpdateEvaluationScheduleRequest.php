<?php

namespace App\Http\Requests\Admin;

use App\Models\EvaluationSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEvaluationScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'evaluation_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => [
                'required',
                Rule::in(array_column(EvaluationSchedule::TIME_SLOTS, 0)),
                function ($attribute, $value, $fail) {
                    $evaluationDate = \Illuminate\Support\Carbon::parse($this->input('evaluation_date'));

                    if ($evaluationDate->isToday() && $value <= now()->format('H:i')) {
                        $fail('That time slot has already passed today. Please choose a later time.');

                        return;
                    }

                    $currentId = $this->route('evaluationSchedule')?->evaluation_schedule_id;

                    $taken = EvaluationSchedule::where('status', 'Scheduled')
                        ->whereDate('evaluation_date', $this->input('evaluation_date'))
                        ->where('start_time', $value)
                        ->when($currentId, fn ($q) => $q->where('evaluation_schedule_id', '!=', $currentId))
                        ->exists();

                    if ($taken) {
                        $fail('That time slot is already booked on the selected date.');
                    }
                },
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'evaluation_date.after_or_equal' => 'You cannot schedule an evaluation in the past.',
        ];
    }
}
