<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCohortRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        // Also used (unmodified) by UpdateCohortRequest, which extends this
        // class — {cohort} only resolves on the update route, so the
        // ignore() below is naturally skipped on store and applied on
        // update, excluding the cohort's own row from the uniqueness check.
        $uniqueLabel = Rule::unique('cohorts', 'label');
        if ($cohort = $this->route('cohort')) {
            $uniqueLabel = $uniqueLabel->ignore($cohort->cohort_id, 'cohort_id');
        }

        return [
            // "Cohort Name" in the UI — the underlying number (used
            // elsewhere in the app as Startup::cohort_number) is
            // auto-assigned by the controller, not user-entered.
            'label' => ['required', 'string', 'max:100', $uniqueLabel],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'Please enter a cohort name.',
            'label.unique' => 'A cohort with this name already exists — cohort names must be unique.',
            'start_date.required' => 'Please enter a start date.',
            'end_date.required' => 'Please enter an end date.',
            'end_date.after_or_equal' => 'End date must be on or after the start date.',
        ];
    }
}
