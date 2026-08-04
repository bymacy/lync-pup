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
            'meeting_date' => ['required', 'date'],
            'meeting_start_time' => ['required', 'date_format:H:i'],
            'meeting_end_time' => ['required', 'date_format:H:i', 'after:meeting_start_time'],
            'meeting_platform' => ['required', 'in:Google Meet,Zoom,Microsoft Teams,Other'],
            'meeting_link' => ['required', 'string', 'max:255'],
        ];
    }
}
