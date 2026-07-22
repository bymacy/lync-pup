<?php

namespace App\Http\Requests\Startup;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncubationInvolvementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStartup();
    }

    public function rules(): array
    {
        return [
            'organization_name_address' => ['required', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'number_of_hours' => ['nullable', 'string', 'max:20'],
            'incubation_program_focus' => ['nullable', 'string', 'max:255'],
        ];
    }
}