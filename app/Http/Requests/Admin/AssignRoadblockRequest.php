<?php

namespace App\Http\Requests\Admin;

use App\Models\Roadblock;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AssignRoadblockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'mentor_id' => ['required', 'exists:mentors,mentor_id'],
            'meeting_date' => ['required', 'date', 'after_or_equal:today'],
            'meeting_start_time' => [
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    $meetingDate = \Illuminate\Support\Carbon::parse($this->input('meeting_date'));

                    if ($meetingDate->isToday() && $value <= now()->format('H:i')) {
                        $fail('That time has already passed today. Please choose a later time.');
                    }
                },
            ],
            'meeting_end_time' => ['required', 'date_format:H:i', 'after:meeting_start_time'],
            'meeting_platform' => ['required', 'in:Google Meet,Zoom,Microsoft Teams,Other'],
            'meeting_link' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'meeting_date.after_or_equal' => 'You cannot schedule a meeting in the past.',
        ];
    }

    /**
     * Cross-field check: make sure the chosen mentor isn't already booked
     * on another Scheduled roadblock whose meeting time overlaps this one.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('mentor_id') || ! $this->filled('meeting_date')
                || ! $this->filled('meeting_start_time') || ! $this->filled('meeting_end_time')) {
                return;
            }

            $currentRoadblockId = $this->route('roadblock')?->roadblock_id;

            $hasConflict = Roadblock::where('mentor_id', $this->input('mentor_id'))
                ->where('status', 'Scheduled')
                ->whereDate('meeting_date', $this->input('meeting_date'))
                ->when($currentRoadblockId, fn ($q) => $q->where('roadblock_id', '!=', $currentRoadblockId))
                ->where('meeting_start_time', '<', $this->input('meeting_end_time'))
                ->where('meeting_end_time', '>', $this->input('meeting_start_time'))
                ->exists();

            if ($hasConflict) {
                $validator->errors()->add(
                    'meeting_start_time',
                    'This mentor already has another meeting scheduled during that time.'
                );
            }
        });
    }
}
