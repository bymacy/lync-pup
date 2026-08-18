<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveFounderApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'cohort_id' => ['required', 'exists:cohorts,cohort_id'],
            'coordinator_id' => ['nullable', 'exists:coordinators,coordinator_id'],
            'admin_remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'cohort_id.required' => 'Please assign this founder to a cohort.',
        ];
    }
}
