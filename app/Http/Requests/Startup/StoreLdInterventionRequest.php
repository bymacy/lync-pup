<?php

namespace App\Http\Requests\Startup;

use Illuminate\Foundation\Http\FormRequest;

class StoreLdInterventionRequest extends FormRequest
{
    use SheetRowRules;

    public function authorize(): bool
    {
        return $this->user()->isStartup();
    }

    /**
     * Section IV of the Information Sheet. Every column is required — see
     * SheetRowRules for why, and for the shared column shapes.
     */
    public function rules(): array
    {
        return [
            'title' => $this->rowText(255),
            'date_from' => ['required', 'date', 'after:1900-01-01'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'number_of_hours' => $this->rowHours(),
            'conducted_sponsored_by' => $this->rowText(255),
        ];
    }

    public function messages(): array
    {
        return $this->rowMessages([
            'title.required' => 'Enter the title of the training or program.',
            'date_from.required' => 'Select the date the program started.',
            'date_to.required' => 'Select the date the program ended.',
            'date_to.after_or_equal' => 'The end date cannot be earlier than the start date.',
            'number_of_hours.required' => 'Enter the number of hours, or N/A if it was not tracked.',
            'number_of_hours.regex' => 'Number of hours must be a number, for example 8.',
            'conducted_sponsored_by.required' => 'Enter who conducted or sponsored the program.',
        ]);
    }
}
