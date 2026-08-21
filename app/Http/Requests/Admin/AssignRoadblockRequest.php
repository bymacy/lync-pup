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
        // Original stored values (if any) — used below so editing an
        // already-Scheduled or Failed roadblock without actually changing
        // its date/time never gets rejected for "being in the past", even
        // though real time has moved on since it was first scheduled.
        $roadblock = $this->route('roadblock');
        $originalDate = $roadblock?->meeting_date?->format('Y-m-d');
        $originalStart = $roadblock?->meeting_start_time ? substr($roadblock->meeting_start_time, 0, 5) : null;

        return [
            // Only mentor_id carries required_without — if coordinator_id
            // also had its own required_without:mentor_id, leaving BOTH
            // blank failed both rules at once and showed the same "select
            // a mentor or coordinator" message twice. One field owning the
            // check is enough: it's satisfied the moment either is filled.
            'mentor_id' => ['nullable', 'required_without:coordinator_id', 'exists:mentors,mentor_id'],
            'coordinator_id' => ['nullable', 'exists:coordinators,coordinator_id'],
            'meeting_date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($originalDate) {
                    // Unchanged from what's already saved — don't re-check
                    // "is this in the future", it was valid when it was set.
                    if ($originalDate && $value === $originalDate) {
                        return;
                    }

                    if (\Illuminate\Support\Carbon::parse($value)->startOfDay()->lt(now()->startOfDay())) {
                        $fail('You cannot schedule a meeting in the past.');
                    }
                },
            ],
            // 'required' stays as its own rule so Laravel still catches a
            // completely missing field (a bare closure only runs when the
            // field is present at all). The closure itself skips blank
            // values instead of re-asserting its own "required" message, so
            // each field only ever shows ONE message instead of "required"
            // and a second, redundant complaint stacking on top of it.
            //
            // The format check and the "already passed" / "must be after
            // start" checks are combined into one closure (rather than
            // separate 'date_format' + 'after' rules) so a blank or
            // not-yet-meaningful value never trips them:
            //   - Carbon::parse('') silently returns "now" instead of
            //     failing, so with meeting_date left blank the old
            //     'already passed today' check on start time could fire
            //     even though there was no real date to compare against.
            //   - With both fields blank, string-comparing '' <= '14:23'
            //     is true, so the same check could wrongly fire on an
            //     empty start time too, instead of leaving that to
            //     'required'.
            'meeting_start_time' => [
                'required',
                function ($attribute, $value, $fail) use ($originalDate, $originalStart) {
                    if (blank($value)) {
                        return;
                    }

                    if (! preg_match('/^\d{2}:\d{2}$/', $value)) {
                        $fail('The meeting start time field must match the format H:i.');

                        return;
                    }

                    if (blank($this->input('meeting_date'))) {
                        // No date to judge "already passed" against yet —
                        // that's meeting_date's own error to raise.
                        return;
                    }

                    $unchanged = $originalDate && $originalStart
                        && $this->input('meeting_date') === $originalDate
                        && $value === $originalStart;

                    if ($unchanged) {
                        return;
                    }

                    $meetingDate = \Illuminate\Support\Carbon::parse($this->input('meeting_date'));

                    if ($meetingDate->isToday() && $value <= now()->format('H:i')) {
                        $fail('That time has already passed today. Please choose a later time.');
                    }
                },
            ],
            'meeting_end_time' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (blank($value)) {
                        return;
                    }

                    if (! preg_match('/^\d{2}:\d{2}$/', $value)) {
                        $fail('The meeting end time field must match the format H:i.');

                        return;
                    }

                    $start = $this->input('meeting_start_time');

                    if (filled($start) && preg_match('/^\d{2}:\d{2}$/', $start) && $value <= $start) {
                        $fail('The meeting end time field must be a time after the start time.');
                    }
                },
            ],
            'meeting_platform' => ['required', 'in:Google Meet,Zoom,Microsoft Teams,Location,Other'],
            'meeting_link' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'mentor_id.required_without' => 'Please select a mentor or coordinator.',
        ];
    }

    public function attributes(): array
    {
        return [
            // Matches the "Meeting Link / Location" field label — the
            // default "meeting link" wording didn't make sense once this
            // field could also hold a physical address (Location platform).
            'meeting_link' => 'meeting link / location',
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
            $currentRoadblock = $this->route('roadblock');
            $currentRoadblockId = $currentRoadblock?->roadblock_id;

            $overlapsWindow = fn ($query) => $query
                ->where('status', 'Scheduled')
                ->whereDate('meeting_date', $this->input('meeting_date'))
                ->when($currentRoadblockId, fn ($q) => $q->where('roadblock_id', '!=', $currentRoadblockId))
                ->where('meeting_start_time', '<', $this->input('meeting_end_time'))
                ->where('meeting_end_time', '>', $this->input('meeting_start_time'));

            $hasConflict = $overlapsWindow(Roadblock::where($column, $assigneeId))->exists();

            if ($hasConflict) {
                $validator->errors()->add(
                    'meeting_start_time',
                    ($isMentor ? 'This mentor' : 'This coordinator').' already has another meeting scheduled during that time.'
                );
            }

            // Separate from the mentor/coordinator check above: a startup
            // can't be in two meetings at once either, even if each one is
            // with a different mentor/coordinator. Testers found this gap —
            // e.g. two different mentors could both get booked against the
            // same startup at the same time, which the assignee-only check
            // above never catches since it looks at mentor_id/coordinator_id,
            // not startup_id.
            $startupId = $currentRoadblock?->startup_id;

            if ($startupId) {
                $hasStartupConflict = $overlapsWindow(Roadblock::where('startup_id', $startupId))->exists();

                if ($hasStartupConflict) {
                    $validator->errors()->add(
                        'meeting_start_time',
                        'This startup already has another meeting scheduled during that time.'
                    );
                }
            }
        });
    }
}
