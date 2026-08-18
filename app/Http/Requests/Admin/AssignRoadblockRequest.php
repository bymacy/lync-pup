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

    /**
     * The Assign & Schedule form submits a single "assignee" select whose
     * option values look like "mentor-5" or "coordinator-3" (mentors and
     * coordinators share one dropdown with a separator between them). Split
     * that back into the two real columns before validation runs, so the
     * rest of this class — and the controller's mass update — only ever
     * has to deal with plain mentor_id / coordinator_id values.
     *
     * Requests that post mentor_id directly (no "assignee" field) are left
     * untouched, so this stays backward compatible with anything that still
     * submits the old-style single mentor_id field.
     */
    protected function prepareForValidation(): void
    {
        $assignee = $this->input('assignee');

        if (! $assignee || ! str_contains($assignee, '-')) {
            return;
        }

        [$type, $id] = explode('-', $assignee, 2);

        $this->merge([
            'mentor_id' => $type === 'mentor' ? $id : null,
            'coordinator_id' => $type === 'coordinator' ? $id : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'mentor_id' => ['nullable', 'required_without:coordinator_id', 'exists:mentors,mentor_id'],
            'coordinator_id' => ['nullable', 'required_without:mentor_id', 'exists:coordinators,coordinator_id'],
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
            'mentor_id.required_without' => 'Please select a mentor or coordinator.',
            'coordinator_id.required_without' => 'Please select a mentor or coordinator.',
        ];
    }

    /**
     * Cross-field check: make sure whoever was picked (mentor or coordinator)
     * isn't already booked on another Scheduled roadblock whose meeting time
     * overlaps this one.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $isMentor = $this->filled('mentor_id');
            $isCoordinator = $this->filled('coordinator_id');

            if ((! $isMentor && ! $isCoordinator) || ! $this->filled('meeting_date')
                || ! $this->filled('meeting_start_time') || ! $this->filled('meeting_end_time')) {
                return;
            }

            $column = $isMentor ? 'mentor_id' : 'coordinator_id';
            $assigneeId = $isMentor ? $this->input('mentor_id') : $this->input('coordinator_id');
            $currentRoadblockId = $this->route('roadblock')?->roadblock_id;

            $hasConflict = Roadblock::where($column, $assigneeId)
                ->where('status', 'Scheduled')
                ->whereDate('meeting_date', $this->input('meeting_date'))
                ->when($currentRoadblockId, fn ($q) => $q->where('roadblock_id', '!=', $currentRoadblockId))
                ->where('meeting_start_time', '<', $this->input('meeting_end_time'))
                ->where('meeting_end_time', '>', $this->input('meeting_start_time'))
                ->exists();

            if ($hasConflict) {
                $validator->errors()->add(
                    'meeting_start_time',
                    ($isMentor ? 'This mentor' : 'This coordinator').' already has another meeting scheduled during that time.'
                );
            }
        });
    }
}
