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
        $cohortId = $this->route('cohort')?->cohort_id;

        return [
            'number' => [
                'required', 'integer', 'min:1',
                Rule::unique('cohorts', 'number')->ignore($cohortId, 'cohort_id'),
            ],
            'label' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:Active,Inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'number.required' => 'Please enter a cohort number.',
            'number.unique' => 'That cohort number already exists.',
        ];
    }
}
