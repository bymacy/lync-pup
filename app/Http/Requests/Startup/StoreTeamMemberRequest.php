<?php

namespace App\Http\Requests\Startup;

use App\Support\SheetOptions;
use Illuminate\Foundation\Http\FormRequest;

class StoreTeamMemberRequest extends FormRequest
{
    use SheetRowRules;

    public function authorize(): bool
    {
        return $this->user()->isStartup();
    }

    /**
     * Section II, item 24 of the Information Sheet. Every column is required —
     * see SheetRowRules for why, and for the shared column shapes.
     */
    public function rules(): array
    {
        return [
            // These four use their own dedicated shapes (rowPersonName,
            // rowDesignation, rowAddress, rowCitizenship) rather than the
            // rowName/rowText/rowWords shared with References, Incubation
            // and L&D - a Core Team member is a real, named person, so
            // (unlike those other rows) none of these accept N/A here.
            'full_name' => $this->rowPersonName(150),
            'designation' => $this->rowDesignation(100),
            'phone' => $this->rowPhone(),
            'address' => $this->rowAddress(255),
            'date_of_birth' => ['required', 'date', 'before:2010-01-01', 'after:1900-01-01'],
            'email' => ['required', 'email', 'max:150'],
            'citizenship' => $this->rowCitizenship(100),
            // Both are dropdowns on the sheet, so the list is the rule.
            'sex' => ['required', 'string', 'in:'.implode(',', SheetOptions::sexes())],
            'civil_status' => ['required', 'string', 'in:'.implode(',', SheetOptions::civilStatuses())],
        ];
    }

    public function messages(): array
    {
        return $this->rowMessages([
            'full_name.required' => 'Please enter the team member\'s name.',
            'full_name.regex' => 'Please enter a valid name.',
            'designation.required' => 'Please enter the team member\'s designation.',
            'phone.required' => 'Please enter the team member\'s phone number.',
            'phone.regex' => 'Please enter a valid phone number.',
            'address.required' => 'Please enter the team member\'s address.',
            'address.regex' => 'Please enter a valid address.',
            'date_of_birth.required' => 'Select the team member\'s date of birth.',
            'date_of_birth.before' => 'Date of birth must be 2009 or earlier.',
            'email.required' => 'Enter the team member\'s email address.',
            'citizenship.required' => 'Please enter the team member\'s citizenship.',
            'citizenship.regex' => 'Please enter a valid citizenship.',
            'sex.required' => 'Please select the team member\'s sex.',
            'sex.in' => 'Choose Male or Female.',
            'civil_status.required' => 'Please select the team member\'s civil status.',
            'civil_status.in' => 'Choose one of the listed civil statuses.',
        ]);
    }
}
