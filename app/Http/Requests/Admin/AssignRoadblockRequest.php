<?php

namespace App\Http\Requests\Admin;

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
}
