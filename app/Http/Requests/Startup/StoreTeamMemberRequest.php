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
            'full_name' => $this->rowName(150),
            'designation' => $this->rowText(100),
            'phone' => $this->rowPhone(),
            'address' => $this->rowText(255),
            'date_of_birth' => ['required', 'date', 'before:2010-01-01', 'after:1900-01-01'],
            'email' => ['required', 'email', 'max:150'],
            'citizenship' => $this->rowWords(100),
            // Both are dropdowns on the sheet, so the list is the rule.
            'sex' => ['required', 'string', 'in:'.implode(',', SheetOptions::sexes())],
            'civil_status' => ['required', 'string', 'in:'.implode(',', SheetOptions::civilStatuses())],
        ];
    }

    public function messages(): array
    {
        return $this->rowMessages([
            'full_name.required' => 'Enter the team member\'s name, surname first.',
            'designation.required' => 'Enter the team member\'s designation.',
            'phone.required' => 'Enter the team member\'s mobile number.',
            'phone.regex' => 'Enter a valid Philippine mobile number, for example 09171234567.',
            'address.required' => 'Enter the team member\'s address.',
            'date_of_birth.required' => 'Select the team member\'s date of birth.',
            'date_of_birth.before' => 'Date of birth must be 2009 or earlier.',
            'email.required' => 'Enter the team member\'s email address.',
            'citizenship.required' => 'Enter the team member\'s citizenship, for example Filipino.',
            'sex.required' => 'Choose the team member\'s sex.',
            'sex.in' => 'Choose Male or Female.',
            'civil_status.required' => 'Choose the team member\'s civil status.',
            'civil_status.in' => 'Choose one of the listed civil statuses.',
        ]);
    }
}
