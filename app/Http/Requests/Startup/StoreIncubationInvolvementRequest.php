<?php

namespace App\Http\Requests\Startup;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncubationInvolvementRequest extends FormRequest
{
    use SheetRowRules;

    public function authorize(): bool
    {
        return $this->user()->isStartup();
    }

    /**
     * Section III of the Information Sheet. Every column is required — see
     * SheetRowRules for why, and for the shared column shapes.
     */
    public function rules(): array
    {
        return [
            'organization_name_address' => $this->rowText(255),
            'date_from' => ['required', 'date', 'after:1900-01-01'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'number_of_hours' => $this->rowHours(),
            'incubation_program_focus' => $this->rowText(255),
        ];
    }

    public function messages(): array
    {
        return $this->rowMessages([
            'organization_name_address.required' => 'Enter the organization name and address.',
            'date_from.required' => 'Select the date the involvement started.',
            'date_to.required' => 'Select the date the involvement ended.',
            'date_to.after_or_equal' => 'The end date cannot be earlier than the start date.',
            'number_of_hours.required' => 'Enter the number of hours, or N/A if it was not tracked.',
            'number_of_hours.regex' => 'Number of hours must be a number, for example 40.',
            'incubation_program_focus.required' => 'Enter the focus of the incubation program.',
        ]);
    }
}
