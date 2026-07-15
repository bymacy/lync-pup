<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignCoordinatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'coordinator_id' => ['required', 'exists:coordinators,coordinator_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'coordinator_id.required' => 'Please select a Portfolio Coordinator.',
            'coordinator_id.exists' => 'The selected coordinator no longer exists.',
        ];
    }
}