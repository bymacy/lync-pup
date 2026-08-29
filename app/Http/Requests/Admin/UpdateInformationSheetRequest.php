<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInformationSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * PUP-TBIDO Form No. 001 is filled out in capital letters, so the entries
     * are upper-cased on the way in — the inputs also render uppercase, and
     * this makes the stored value match what the founder sees (and what the
     * PDF export prints).
     *
     * Email is deliberately excluded: the part before the @ is case-sensitive
     * on some mail servers, so upper-casing it can break delivery.
     */
    protected function prepareForValidation(): void
    {
        $upper = [
            // I. Founder's information
            'surname', 'first_name', 'middle_name', 'name_extension', 'blood_type',
            'gsis_no', 'pagibig_no', 'philhealth_no', 'sss_no',
            'residential_address', 'permanent_address', 'sex', 'civil_status',
            'citizenship_by_birth', 'citizenship_dual', 'place_of_birth',
            // 28-31. Startup registration
            'sec_registration', 'business_id_number', 'dti_registration_number', 'business_tin',
            // 32 & 34. Distinctions and memberships
            'non_academic_distinctions', 'membership_associations',
            // For TBIDO only
            'portfolio_manager', 'cohort_no', 'endorsed_by',
        ];

        // Fields that legitimately hold several lines. Everything else is a
        // one-line answer, so a pasted line break is folded into a space —
        // the inputs prevent Enter, but paste can still smuggle one in.
        $multiline = [
            'startup_overview', 'scholarships_academic_honors',
            'non_academic_distinctions', 'membership_associations',
        ];

        $payload = [];

        foreach ($this->all() as $field => $value) {
            if (! is_string($value) || in_array($field, ['_token', '_method'], true)) {
                continue;
            }

            $clean = in_array($field, $multiline, true)
                ? trim($value)
                : preg_replace('/\s*\R\s*/u', ' ', trim($value));

            if (in_array($field, $upper, true)) {
                $clean = mb_strtoupper($clean, 'UTF-8');
            }

            if ($clean !== $value) {
                $payload[$field] = $clean;
            }
        }

        if ($payload) {
            $this->merge($payload);
        }
    }

    /**
     * Once this startup has a scheduled evaluation, fields that already
     * hold a value can be replaced but not cleared — see
     * InformationSheet::blankedFields() and Startup::hasScheduledEvaluation().
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $startup = $this->route('startup');
            $sheet = $startup?->informationSheet;

            if (! $sheet || ! $startup->hasScheduledEvaluation()) {
                return;
            }

            $data = collect($validator->getData())
                ->except(['_token', '_method'])
                ->all();

            foreach ($sheet->blankedFields($data) as $field) {
                $validator->errors()->add(
                    $field,
                    'This field cannot be cleared once an evaluation has been scheduled — please keep or replace the existing value.'
                );
            }
        });
    }

    /**
     * Mirrors the founder's rule set: every field the sheet actually renders is
     * required, because the PUP form says "Indicate N/A If Not Applicable" —
     * a blank means unanswered, not inapplicable. Text and ID fields therefore
     * accept the literal "N/A"; typed fields (email, phone, dates, height,
     * weight, year graduated) still need real values.
     *
     * Left nullable on purpose: director_approval_date (filled after the
     * director signs the printed copy) and target_market / problem_statement /
     * solution_offered, which have no edit UI on this page — requiring a field
     * nobody can fill would block every save.
     */
    public function rules(): array
    {
        $text = fn (int $max) => ['required', 'string', 'max:'.$max];

        // Names: letters only (plus the punctuation real names carry — spaces,
        // hyphens, apostrophes, periods, Ñ/accents). No digits, no symbols.
        $name = fn (int $max) => [
            'required', 'string', 'max:'.$max,
            'regex:/^(n\/a|[\p{L}][\p{L}\s\.\-\x{2019}\']*)$/iu',
        ];

        // ID numbers: digits with optional spaces or dashes, e.g. 12-3456789-0.
        // No letters, so a typo like "SSS 12" is caught here.
        $idNumber = fn (int $max) => [
            'required', 'string', 'max:'.$max,
            'regex:/^(n\/a|[0-9][0-9\s\-]*)$/i',
        ];

        // Words only: letters plus the punctuation that shows up inside real
        // words (spaces, hyphens, apostrophes, periods). Used for answers that
        // are never numeric — sex, civil status, citizenship.
        $words = fn (int $max) => [
            'required', 'string', 'max:'.$max,
            'regex:/^(n\/a|[\p{L}][\p{L}\s\.\-\x{2019}\']*)$/iu',
        ];

        // Place names: words plus commas, e.g. "Sta. Mesa, Manila".
        $place = fn (int $max) => [
            'required', 'string', 'max:'.$max,
            'regex:/^(n\/a|[\p{L}][\p{L}\s\.\,\-\x{2019}\']*)$/iu',
        ];

        // Addresses: words and house/unit numbers, plus the punctuation an
        // address actually uses. Rejects @ ! $ % ^ * = < > and friends.
        $address = fn (int $max) => [
            'required', 'string', 'max:'.$max,
            'regex:/^(n\/a|[\p{L}\p{N}\#][\p{L}\p{N}\s\.\,\-\#\/\(\)\&\x{2019}\']*)$/iu',
        ];

        // Schools, courses and units earned: same shape as an address minus the
        // unit sign — "Polytechnic University of the Philippines", "St. Paul's
        // College", "36 units", "Bachelor's Degree".
        $institution = fn (int $max) => [
            'required', 'string', 'max:'.$max,
            'regex:/^(n\/a|[\p{L}\p{N}][\p{L}\p{N}\s\.\,\-\/\(\)\&\x{2019}\']*)$/iu',
        ];

        // Blood type: A, B, AB or O with a + or - sign.
        $bloodType = [
            'required', 'string', 'max:10',
            'regex:/^(n\/a|(a|b|ab|o)\s?[+\-])$/i',
        ];

        // Free-form paragraphs. Punctuation is expected here, so only the
        // markup characters are blocked.
        $prose = fn (int $max) => [
            'required', 'string', 'max:'.$max,
            'regex:/^[^<>{}|\\^~]*$/u',
        ];

        // Registration codes are deliberately mixed — "CS201812345",
        // "DTI-0054321", "07-000123-4". Letters and digits are both fine; what
        // is rejected is punctuation that never appears in a real reference
        // number (@, #, emoji, quotes), so a junk entry can't pass as a code.
        $code = fn (int $max) => [
            'required', 'string', 'max:'.$max,
            'regex:/^(n\/a|[A-Za-z0-9][A-Za-z0-9\s\-\/\.]*)$/i',
        ];

        // "N/A" or a number (e.g. 1.65 / 58)
        $measurement = ['required', 'string', 'max:20', 'regex:/^(n\/a|\d+(\.\d+)?)$/i'];

        // "N/A" or a 4-digit year
        $year = ['required', 'string', 'max:10', 'regex:/^(n\/a|(19|20)\d{2})$/i'];

        return [
            'surname' => $name(100),
            'first_name' => $name(100),
            'middle_name' => $name(100),
            'name_extension' => $name(20),
            'height_m' => $measurement,
            'weight_kg' => $measurement,
            'blood_type' => $bloodType,
            'gsis_no' => $idNumber(50),
            'pagibig_no' => $idNumber(50),
            'philhealth_no' => $idNumber(50),
            'sss_no' => $idNumber(50),
            'residential_address' => $address(255),
            'permanent_address' => $address(255),
            'sex' => $words(20),
            'civil_status' => $words(30),
            'citizenship_by_birth' => $words(100),
            'citizenship_dual' => $words(100),
            'place_of_birth' => $place(150),
            'date_of_birth' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'mobile_no' => ['required', 'string', 'max:20', 'regex:/^(\+63|0)9\d{2}[ -]?\d{3}[ -]?\d{4}$/'],
            'founder_email' => ['required', 'email', 'max:150'],

            'secondary_school' => $institution(150),
            'secondary_degree_course' => $institution(150),
            'secondary_highest_level_unit' => $institution(100),
            'secondary_year_graduated' => $year,
            'vocational_school' => $institution(150),
            'vocational_degree_course' => $institution(150),
            'vocational_highest_level_unit' => $institution(100),
            'vocational_year_graduated' => $year,
            'college_school' => $institution(150),
            'college_degree_course' => $institution(150),
            'college_highest_level_unit' => $institution(100),
            'college_year_graduated' => $year,
            'graduate_school' => $institution(150),
            'graduate_degree_course' => $institution(150),
            'graduate_highest_level_unit' => $institution(100),
            'graduate_year_graduated' => $year,
            'scholarships_academic_honors' => $prose(2000),

            // The sheet's own overview column, separate from the Startup
            // Profile's business_description (which this page no longer writes).
            'startup_overview' => $prose(5000),

            // Written by the founder's Startup Profile; kept here so an
            // existing value survives a save from this page.
            'business_description' => ['nullable', 'string', 'max:5000'],

            // No edit UI on this page yet, so these stay optional.
            'target_market' => ['nullable', 'string'],
            'problem_statement' => ['nullable', 'string'],
            'solution_offered' => ['nullable', 'string'],

            'sec_registration' => $code(100),
            'business_id_number' => $code(100),
            'dti_registration_number' => $code(100),
            'business_tin' => $idNumber(100),
            'non_academic_distinctions' => $prose(2000),
            'membership_associations' => $prose(2000),

            // Declaration & Endorsement (TBIDO-side fields — never editable by the founder)
            // Stamped by the controller on save — never typed, so it is not
            // validated as user input.
            'portfolio_manager' => $words(150),
            'cohort_no' => $code(20),
            'endorsed_by' => $words(150),
            'endorsement_date' => ['required', 'date'],

            // Filled in after the director signs the printed copy, so it stays
            // optional while everything else on the form is required.
            'director_approval_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        $messages = [
            // Personal information
            'surname.required' => 'Enter the founder\'s surname.',
            'surname.regex' => 'Surname can only contain letters, spaces, hyphens and periods.',
            'first_name.required' => 'Enter the founder\'s first name.',
            'first_name.regex' => 'First name can only contain letters, spaces, hyphens and periods.',
            'middle_name.required' => 'Enter the middle name, or N/A if there is none.',
            'middle_name.regex' => 'Middle name can only contain letters, spaces, hyphens and periods.',
            'name_extension.required' => 'Enter a name extension such as Jr., Sr. or III, or N/A if there is none.',
            'name_extension.regex' => 'Name extension can only contain letters and periods.',

            'height_m.required' => 'Enter the height in meters, for example 1.65.',
            'height_m.regex' => 'Height must be a number in meters, for example 1.65.',
            'weight_kg.required' => 'Enter the weight in kilograms, for example 58.',
            'weight_kg.regex' => 'Weight must be a number in kilograms, for example 58.',
            'blood_type.required' => 'Enter the blood type, for example O+.',

            'gsis_no.required' => 'Enter the GSIS number, or N/A if there is none.',
            'gsis_no.regex' => 'GSIS number must contain digits only (dashes and spaces are allowed).',
            'pagibig_no.required' => 'Enter the Pag-IBIG MID number, or N/A if there is none.',
            'pagibig_no.regex' => 'Pag-IBIG number must contain digits only (dashes and spaces are allowed).',
            'philhealth_no.required' => 'Enter the PhilHealth number, or N/A if there is none.',
            'philhealth_no.regex' => 'PhilHealth number must contain digits only (dashes and spaces are allowed).',
            'sss_no.required' => 'Enter the SSS number, or N/A if there is none.',
            'sss_no.regex' => 'SSS number must contain digits only (dashes and spaces are allowed).',

            'residential_address.required' => 'Enter the current residential address.',
            'residential_address.regex' => 'Use letters, numbers and normal address punctuation only (. , - # / & ).',
            'permanent_address.regex' => 'Use letters, numbers and normal address punctuation only (. , - # / & ).',
            'blood_type.regex' => 'Enter a blood type such as O+, A-, AB+.',
            'sex.regex' => 'Use letters only.',
            'civil_status.regex' => 'Use letters only, for example Single or Married.',
            'citizenship_by_birth.regex' => 'Use letters only, for example Filipino.',
            'citizenship_dual.regex' => 'Use letters only, or N/A if there is none.',
            'place_of_birth.regex' => 'Use letters, commas and periods only, for example Sta. Mesa, Manila.',
            'scholarships_academic_honors.regex' => 'Remove the < > { } | \\ ^ ~ characters.',
            'non_academic_distinctions.regex' => 'Remove the < > { } | \\ ^ ~ characters.',
            'membership_associations.regex' => 'Remove the < > { } | \\ ^ ~ characters.',
            'startup_overview.regex' => 'Remove the < > { } | \\ ^ ~ characters.',
            'portfolio_manager.regex' => 'Use letters only.',
            'endorsed_by.regex' => 'Use letters only.',
            'permanent_address.required' => 'Enter the permanent address. Repeat the residential address if they are the same.',
            'sex.required' => 'Enter the sex as written on official records.',
            'civil_status.required' => 'Enter the civil status, for example Single or Married.',
            'citizenship_by_birth.required' => 'Enter the citizenship by birth, for example Filipino.',
            'citizenship_dual.required' => 'Enter the second citizenship, or N/A if there is none.',
            'place_of_birth.required' => 'Enter the city or municipality of birth.',
            'date_of_birth.required' => 'Select the date of birth.',
            'date_of_birth.date' => 'Enter the date of birth as a valid date.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'date_of_birth.after' => 'Check the date of birth — the year looks too early.',

            'mobile_no.required' => 'Enter an active mobile number, for example 09171234567.',
            'mobile_no.regex' => 'Enter a valid Philippine mobile number, for example 09171234567 or +639171234567.',
            'founder_email.required' => 'Enter an email address that can receive updates.',
            'founder_email.email' => 'Enter a valid email address, for example name@email.com.',

            // Business registration
            'sec_registration.required' => 'Enter the SEC registration number, or N/A if not registered.',
            'business_id_number.required' => 'Enter the business ID number, or N/A if there is none.',
            'dti_registration_number.required' => 'Enter the DTI registration number, or N/A if not registered.',
            'business_tin.required' => 'Enter the business TIN, or N/A if there is none.',
            'business_tin.regex' => 'TIN must contain digits only (dashes and spaces are allowed).',
            'sec_registration.regex' => 'SEC registration can only contain letters, numbers, dashes and slashes.',
            'business_id_number.regex' => 'Business ID number can only contain letters, numbers, dashes and slashes.',
            'dti_registration_number.regex' => 'DTI registration can only contain letters, numbers, dashes and slashes.',
            'cohort_no.regex' => 'Cohort no. can only contain letters and numbers, for example Cohort 3.',

            // Long-form entries
            'scholarships_academic_honors.required' => 'List any scholarships or academic honors received, or write N/A if there are none.',
            'non_academic_distinctions.required' => 'Add at least one non-academic distinction, or one row containing N/A.',
            'membership_associations.required' => 'Add at least one membership in an association or organization, or one row containing N/A.',
            'startup_overview.required' => 'Describe what the startup does.',

            // Declaration
        ];

        // Education table — four levels, four columns each, all worded the same
        // way so the founder is told exactly which row is missing.
        $levels = [
            'secondary' => 'secondary school',
            'vocational' => 'vocational course',
            'college' => 'college',
            'graduate' => 'graduate studies',
        ];

        foreach ($levels as $key => $label) {
            $messages[$key.'_school.required'] = "Enter the name of the {$label} attended, or N/A if not applicable.";
            $messages[$key.'_degree_course.required'] = "Enter the degree or course taken for {$label}, or N/A if not applicable.";
            $messages[$key.'_highest_level_unit.required'] = "Enter the highest level or units earned for {$label}, or N/A if not applicable.";
            $messages[$key.'_year_graduated.required'] = "Enter the year graduated for {$label}, or N/A if not applicable.";
            $messages[$key.'_year_graduated.regex'] = "Year graduated for {$label} must be a 4-digit year, for example 2018.";
            $messages[$key.'_school.regex'] = "The {$label} name can only contain letters, numbers and . , - / & punctuation.";
            $messages[$key.'_degree_course.regex'] = "The {$label} degree or course can only contain letters, numbers and . , - / & punctuation.";
            $messages[$key.'_highest_level_unit.regex'] = "The {$label} level or units can only contain letters, numbers and . , - / & punctuation.";
        }

        $messages['portfolio_manager.required'] = 'Enter the assigned portfolio manager.';
        $messages['cohort_no.required'] = 'Enter the cohort number, for example Cohort 3.';
        $messages['endorsed_by.required'] = 'Enter who endorsed this startup.';
        $messages['endorsement_date.required'] = 'Select the endorsement date.';
        $messages['endorsement_date.date'] = 'Enter the endorsement date as a valid date.';

        // Fallback for anything not named above.
        $messages['required'] = 'This field is required. Enter N/A if it does not apply.';

        return $messages;
    }

    public function attributes(): array
    {
        return [
            'height_m' => 'height',
            'weight_kg' => 'weight',
            'gsis_no' => 'GSIS no.',
            'pagibig_no' => 'Pag-IBIG no.',
            'philhealth_no' => 'PhilHealth no.',
            'sss_no' => 'SSS no.',
            'mobile_no' => 'mobile no.',
            'founder_email' => 'email address',
            'citizenship_by_birth' => 'citizenship by birth',
            'citizenship_dual' => 'dual citizenship',
            'sec_registration' => 'SEC registration',
            'business_tin' => 'business TIN',
            'dti_registration_number' => 'DTI registration number',
            'startup_overview' => 'startup overview',
            'scholarships_academic_honors' => 'scholarships / academic honors',
            'non_academic_distinctions' => 'non-academic distinctions',
            'membership_associations' => 'membership in associations',
            'cohort_no' => 'cohort no.',
        ];
    }
}
