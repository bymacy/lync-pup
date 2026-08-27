<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCohortRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            // "Cohort Name" in the UI — the underlying number (used
            // elsewhere in the app as Startup::cohort_number) is
            // auto-assigned by the controller, not user-entered.
            'label' => ['required', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'Please enter a cohort name.',
            'end_date.after_or_equal' => 'End date must be on or after the start date.',
        ];
    }
}
