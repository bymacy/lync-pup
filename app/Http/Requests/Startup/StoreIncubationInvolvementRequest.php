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
     * Section III of the Information Sheet. A row here is optional at the
     * "should this row exist" level - the founder simply doesn't add one
     * when there's no incubation involvement to report (a blank "add new"
     * row is dropped before it ever reaches here, see
     * submitInfoSheetForms()) - but once a row is added, every cell in it
     * is a real answer, so unlike SheetRowRules' shared column shapes, N/A
     * is not accepted anywhere in this row.
     */
    public function rules(): array
    {
        return [
            // Letters, numbers, spaces, and . , - / & ( ) - and it has to
            // contain an actual letter, so "1234" or a run of punctuation
            // can't pass as an organization name.
            'organization_name_address' => [
                'required', 'string', 'max:255',
                'regex:/^[\p{L}\p{N}][\p{L}\p{N}\s\.\,\-\/\&\(\)]*$/iu',
                function ($attribute, $value, $fail) {
                    if (is_string($value) && ! preg_match('/\p{L}/u', $value)) {
                        $fail('Please enter a valid organization name and address.');
                    }
                },
            ],
            'date_from' => ['required', 'date', 'after:1900-01-01'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            // A whole, positive number of hours - no decimals, no N/A. Unlike
            // rowHours() (shared with L&D, which does allow N/A), this row
            // always has to report a real figure.
            'number_of_hours' => ['required', 'integer', 'min:1'],
            // Free-form description - only markup characters are blocked,
            // same as the sheet's other prose fields - but it still has to
            // contain an actual letter, not just symbols.
            'incubation_program_focus' => [
                'required', 'string', 'max:255',
                'regex:/^[^<>{}|\\^~]*$/u',
                function ($attribute, $value, $fail) {
                    if (is_string($value) && ! preg_match('/\p{L}/u', $value)) {
                        $fail('Please enter a valid incubation program or focus.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return $this->rowMessages([
            'organization_name_address.required' => 'Please enter the organization name and address.',
            'organization_name_address.regex' => 'Please enter a valid organization name and address.',
            'date_from.required' => 'Please enter the start date.',
            'date_from.date' => 'Please enter a valid start date.',
            'date_from.after' => 'Please enter a valid start date.',
            'date_to.required' => 'Please enter the end date.',
            'date_to.date' => 'Please enter a valid end date.',
            'date_to.after_or_equal' => 'End date must be on or after the start date.',
            'number_of_hours.required' => 'Please enter the number of hours.',
            'number_of_hours.integer' => 'Please enter a valid number of hours.',
            'number_of_hours.min' => 'Hours must be greater than 0.',
            'incubation_program_focus.required' => 'Please enter the incubation program or focus.',
            'incubation_program_focus.regex' => 'Please enter a valid incubation program or focus.',
            // Catch-all for this row, replacing SheetRowRules' generic
            // "Type N/A if it does not apply" fallback - nothing in this
            // row accepts N/A, so that wording never applies here.
            'required' => 'Please complete all required information for this organization.',
        ]);
    }
}
