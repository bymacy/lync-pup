<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectInformationSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'evaluator_remarks' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'evaluator_remarks.required' => 'Please provide a reason for rejecting this information sheet.',
        ];
    }
}