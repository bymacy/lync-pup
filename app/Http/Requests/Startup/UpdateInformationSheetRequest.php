<?php

namespace App\Http\Requests\Startup;

use App\Support\SheetOptions;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInformationSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStartup();
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

        // Items 23, 31 and 34 are optional row tables. Emptying one is a real
        // answer - "nothing to declare" - so it is stored as the N/A the paper
        // form asks for, rather than as a blank that reads as "unanswered" in
        // the exports. Only touched when the field was actually submitted, so
        // a partial request cannot wipe an existing entry.
        $blankIsNotApplicable = [
            'scholarships_academic_honors',
            'non_academic_distinctions',
            'membership_associations',
        ];

        foreach ($blankIsNotApplicable as $field) {
            if ($this->has($field) && trim((string) $this->input($field)) === '') {
                $this->merge([$field => 'N/A']);
            }
        }

        // 5 & 6. The founder types a number and picks its unit; the sheet stores
        // metres and kilograms, which is what the column names promise and what
        // the PDF prints. Converting here means every rule below - and
        // blankedFields() - only ever sees the canonical value.
        $measurements = [
            'height_m' => ['input' => 'height_input', 'unit' => 'height_unit', 'factors' => ['cm' => 0.01, 'in' => 0.0254, 'm' => 1.0, 'ft' => 0.3048]],
            'weight_kg' => ['input' => 'weight_input', 'unit' => 'weight_unit', 'factors' => ['kg' => 1.0, 'lb' => 0.45359237]],
        ];

        foreach ($measurements as $target => $spec) {
            if (! $this->has($spec['input'])) {
                continue;
            }

            $raw = trim((string) $this->input($spec['input']));
            $factor = $spec['factors'][$this->input($spec['unit'])] ?? null;

            // A blank, a non-number or an unknown unit is passed through
            // untouched so the rules below produce the message, rather than
            // silently storing a zero.
            $this->merge([
                $target => ($raw === '' || ! is_numeric($raw) || $factor === null)
                    ? $raw
                    : (string) round((float) $raw * $factor, 2),
            ]);
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
            $startup = $this->user()->startup;
            $sheet = $startup?->informationSheet;

            if (! $sheet || ! $startup->hasScheduledEvaluation()) {
                return;
            }

            $data = collect($validator->getData())->except(['_token', '_method'])->all();

            foreach ($sheet->blankedFields($data) as $field) {
                $validator->errors()->add(
                    $field,
                    'This field cannot be cleared once an evaluation has been scheduled — please keep or replace the existing value.'
                );
            }
        });
    }

    /**
     * Every field on the founder's Information Sheet is required — the PUP
     * form itself says "Indicate N/A If Not Applicable", so blanks mean
     * "unanswered", not "doesn't apply". Text and ID fields therefore accept
     * the literal "N/A"; fields with a real type (email, phone, dates,
     * height/weight, year graduated) still have to hold a valid value, since
     * "N/A" in those columns would break exports and downstream parsing.
     */
    public function rules(): array
    {
        $text = fn (int $max) => ['required', 'string', 'max:'.$max];

        // Names: letters only (plus the punctuation real names carry — spaces,
        // hyphens, apostrophes, periods, Ñ/accents). No digits, no symbols.
        // Everyone has a surname and a first name, so - unlike Middle Name and
        // Name Extension just below - N/A is not a valid answer here: the
        // character class has no slash in it, so "N/A" simply can't match.
        $properName = fn (int $max) => [
            'required', 'string', 'max:'.$max,
            'regex:/^[\p{L}][\p{L}\s\.\-\x{2019}\']*$/iu',
        ];

        // Same shape as above, but N/A is a real answer here - not everyone
        // has a middle name or a suffix.
        $name = fn (int $max) => [
            'required', 'string', 'max:'.$max,
            'regex:/^(n\/a|[\p{L}][\p{L}\s\.\-\x{2019}\']*)$/iu',
        ];

        // The business TIN: digits and hyphens only - no spaces, no letters -
        // e.g. 123-456-789-000. This is the only field left using this shape;
        // GSIS/Pag-IBIG/PhilHealth/SSS moved to $govId below for their exact
        // digit counts.
        $idNumber = fn (int $max) => [
            'required', 'string', 'max:'.$max,
            'regex:/^(n\/a|[0-9][0-9\-]*)$/i',
        ];

        // A Philippine government ID number: digits and hyphens only - no
        // spaces - that must total an exact digit count once the hyphens are
        // stripped out. Each agency's number has a fixed, well-known length:
        // GSIS 11, Pag-IBIG 12, PhilHealth 12, SSS 10. Stricter than
        // $idNumber above, which is still used for numbers without a fixed
        // length (e.g. the business TIN).
        $govId = fn (int $digits, string $label) => [
            'required', 'string', 'max:20',
            'regex:/^(n\/a|[0-9\-]+)$/i',
            function ($attribute, $value, $fail) use ($digits, $label) {
                if (is_string($value) && strcasecmp(trim($value), 'N/A') === 0) {
                    return;
                }

                $count = strlen(preg_replace('/[^0-9]/', '', (string) $value));

                if ($count !== $digits) {
                    $fail("Please enter a valid {$label}.");
                }
            },
        ];

        // Words only: letters plus the punctuation that shows up inside real
        // words (spaces, hyphens, apostrophes, periods). Used for answers that
        // are never numeric — sex, civil status, citizenship.
        $words = fn (int $max) => [
            'required', 'string', 'max:'.$max,
            'regex:/^(n\/a|[\p{L}][\p{L}\s\.\-\x{2019}\']*)$/iu',
        ];

        // Place names: words, digits and commas - some barangays and streets are
        // numbered, e.g. "Sta. Mesa, Manila" or "Barangay 176, Caloocan".
        $place = fn (int $max) => [
            'required', 'string', 'max:'.$max,
            'regex:/^(n\/a|[\p{L}\p{N}][\p{L}\p{N}\s\.\,\-\x{2019}\']*)$/iu',
        ];

        // Addresses: words and house/unit numbers, plus the punctuation an
        // address actually uses. Rejects @ ! $ % ^ * = < > and friends. A real
        // address is never this short, so a minimum length is what actually
        // keeps out "N/A" and other non-answers - the character class alone
        // wouldn't, since digits, letters and / are all valid address text.
        $address = fn (int $max) => [
            'required', 'string', 'max:'.$max, 'min:10',
            'regex:/^(n\/a|[\p{L}\p{N}\#][\p{L}\p{N}\s\.\,\-\#\/\(\)\&\x{2019}\']*)$/iu',
        ];

        // Name of School: letters, numbers, spaces, and . - ' & ( ) - no
        // slash, no comma, e.g. "Polytechnic University of the Philippines"
        // or "St. Paul's College".
        $schoolName = fn (int $max) => [
            'required', 'string', 'max:'.$max,
            'regex:/^(n\/a|[\p{L}\p{N}][\p{L}\p{N}\s\.\-\&\(\)\x{2019}\']*)$/iu',
        ];

        // Degree / Course: letters, numbers, spaces, and . - / & ( ) - no
        // apostrophe, no comma, e.g. "BS Computer Science / IT" or
        // "Bachelor's" (spelled without the apostrophe, since that one isn't
        // allowed here).
        $degreeCourse = fn (int $max) => [
            'required', 'string', 'max:'.$max,
            'regex:/^(n\/a|[\p{L}\p{N}][\p{L}\p{N}\s\.\-\/\&\(\)]*)$/iu',
        ];

        // Highest Level / Unit: letters, numbers, spaces, and . - / ( ) - no
        // ampersand, no apostrophe, no comma, e.g. "4th Year" or "36 units".
        $highestLevelUnit = fn (int $max) => [
            'required', 'string', 'max:'.$max,
            'regex:/^(n\/a|[\p{L}\p{N}][\p{L}\p{N}\s\.\-\/\(\)]*)$/iu',
        ];

        // Blood type: A, B, AB or O with a + or - sign.
        $bloodType = [
            'required', 'string', 'max:10',
            'regex:/^(n\/a|(a|b|ab|o)\s?[+\-])$/i',
        ];

        // The startup overview: letters, numbers, spaces and common
        // punctuation . , ! ? ' - ( ) only - no emoji, no HTML/code, no other
        // symbols. Must start with a letter or number, so a string of bare
        // punctuation can't pass as a description.
        $prose = fn (int $max) => [
            'required', 'string', 'max:'.$max,
            'regex:/^[\p{L}\p{N}][\p{L}\p{N}\s\.\,\!\?\'\-\(\)]*$/u',
        ];

        // Items 31 and 34 (Non-Academic Distinctions, Membership in
        // Associations) are row tables the founder may genuinely have nothing
        // to put in, so N/A is a real answer - prepareForValidation() above
        // turns a blank into "N/A" before this even runs. Anything actually
        // typed is restricted to letters, numbers, spaces and . , & ' - ( ) /.
        $optionalProse = fn (int $max) => [
            'nullable', 'string', 'max:'.$max,
            'regex:/^(n\/a|[\p{L}\p{N}][\p{L}\p{N}\s\.\,\&\'\-\(\)\/]*)$/iu',
        ];

        // Item 23 is a packed list (see the row-table widget in the view) -
        // one scholarship/honor per line, normally shaped "<name>, <year>" or
        // "<name>, <year>-<year>", e.g. "Dean's Lister, 2016-2018". The
        // "no markup" check above isn't strict enough to catch a symbols-only
        // entry or a bogus year, so each line gets checked on its own here.
        $scholarshipEntry = function ($attribute, $value, $fail) {
            if (! is_string($value)) {
                return;
            }

            $trimmed = trim($value);

            if ($trimmed === '' || strcasecmp($trimmed, 'N/A') === 0) {
                return;
            }

            $currentYear = (int) date('Y');

            foreach (preg_split('/\r\n|\r|\n/', $trimmed) as $line) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                // Letters, numbers, spaces, and . , - ' & / ( ) - the comma is
                // the separator between the name and the year, not part of
                // the name itself, but it's simplest to allow it everywhere
                // in the line and let the "at least one letter" check below
                // catch an entry that's really just digits or symbols.
                $validChars = preg_match('/^[\p{L}\p{N}][\p{L}\p{N}\s\.,\-\'\&\/\(\)]*$/u', $line);
                $hasLetter = preg_match('/\p{L}/u', $line);

                if (! $validChars || ! $hasLetter) {
                    $fail('Please enter a valid scholarship or academic honor.');
                    return;
                }

                $lastComma = strrpos($line, ',');

                if ($lastComma === false) {
                    continue;
                }

                $yearPart = trim(substr($line, $lastComma + 1));

                // Nothing after the last comma, or nothing that even looks
                // like a year (e.g. "Dean's Lister, College of Engineering")
                // - not every entry has a year, so this isn't required on
                // its own.
                if ($yearPart === '' || ! preg_match('/\d/', $yearPart)) {
                    continue;
                }

                if (preg_match('/^(19|20)\d{2}$/', $yearPart)) {
                    if ((int) $yearPart > $currentYear) {
                        $fail('Please enter a valid year or year range.');
                        return;
                    }
                } elseif (preg_match('/^(19|20)\d{2}\s*-\s*(19|20)\d{2}$/', $yearPart)) {
                    [$from, $to] = array_map('trim', explode('-', $yearPart));

                    if ((int) $from > $currentYear || (int) $to > $currentYear) {
                        $fail('Please enter a valid year or year range.');
                        return;
                    }
                } else {
                    $fail('Please enter a valid year or year range.');
                    return;
                }
            }
        };

        // Registration codes are deliberately mixed — "CS201812345",
        // "DTI-0054321", "07000123". Letters, digits and hyphens only - no
        // spaces, no slash, no period - so a junk entry (or a code typed with
        // stray punctuation) can't pass as one.
        $code = fn (int $max) => [
            'required', 'string', 'max:'.$max,
            'regex:/^(n\/a|[A-Za-z0-9][A-Za-z0-9\-]*)$/i',
        ];


        // "N/A" or a 4-digit year that isn't later than this year - a real
        // transcript can't have graduated someone yet to come.
        $currentYear = (int) date('Y');
        $year = [
            'required', 'string', 'max:10',
            'regex:/^(n\/a|(19|20)\d{2})$/i',
            function ($attribute, $value, $fail) use ($currentYear) {
                if (is_string($value) && strcasecmp(trim($value), 'N/A') === 0) {
                    return;
                }

                if (is_numeric($value) && (int) $value > $currentYear) {
                    $fail('Year graduated cannot be in the future.');
                }
            },
        ];

        return [
            // The sheet's own overview column. Pre-filled from the Startup
            // Profile's business_description the first time, then independent —
            // editing it here never changes the Profile.
            'startup_overview' => $prose(5000),

            'surname' => $properName(100),
            'first_name' => $properName(100),
            'middle_name' => $name(100),
            'name_extension' => $name(20),
            // The two boxes the founder actually types in, plus the unit each
            // one is in. Digits only - the control strips anything else, and
            // this rejects whatever slips past it.
            'height_input' => ['required', 'string', 'max:20', 'regex:/^\d+(\.\d+)?$/'],
            'height_unit' => ['required', 'in:cm,in,m,ft'],
            'weight_input' => ['required', 'string', 'max:20', 'regex:/^\d+(\.\d+)?$/'],
            'weight_unit' => ['required', 'in:kg,lb'],

            // Derived above, then range-checked so a slipped decimal point
            // (17.5 m, 5 kg) is caught before it reaches the export.
            'height_m' => ['required', 'numeric', 'between:0.5,2.5'],
            'weight_kg' => ['required', 'numeric', 'between:20,500'],
            'blood_type' => $bloodType,
            'gsis_no' => $govId(11, 'GSIS ID number'),
            'pagibig_no' => $govId(12, 'PAG-IBIG number'),
            'philhealth_no' => $govId(12, 'PhilHealth number'),
            'sss_no' => $govId(10, 'SSS number'),
            'residential_address' => $address(255),
            'permanent_address' => $address(255),
            // Both come from a fixed control now (segmented buttons / a dropdown),
            // so the list itself is the rule - no spelling variants reach the
            // exports. SheetOptions is the single source of truth for both.
            'sex' => ['required', 'string', 'in:'.implode(',', SheetOptions::sexes())],
            'civil_status' => ['required', 'string', 'in:'.implode(',', SheetOptions::civilStatuses())],
            'citizenship_by_birth' => $words(100),
            'citizenship_dual' => $words(100),
            'place_of_birth' => $place(150),
            // The picker is capped at the same bounds (see $dobMin / $dobMax in the
            // view). Repeated here because a request can arrive without it.
            'date_of_birth' => ['required', 'date', 'before:2010-01-01', 'after:1900-01-01'],
            'mobile_no' => ['required', 'string', 'max:20', 'regex:/^(\+63|0)9\d{2}[ -]?\d{3}[ -]?\d{4}$/'],
            'founder_email' => ['required', 'email', 'max:150'],

            'secondary_school' => $schoolName(150),
            'secondary_degree_course' => $degreeCourse(150),
            'secondary_highest_level_unit' => $highestLevelUnit(100),
            'secondary_year_graduated' => $year,
            'vocational_school' => $schoolName(150),
            'vocational_degree_course' => $degreeCourse(150),
            'vocational_highest_level_unit' => $highestLevelUnit(100),
            'vocational_year_graduated' => $year,
            'college_school' => $schoolName(150),
            'college_degree_course' => $degreeCourse(150),
            'college_highest_level_unit' => $highestLevelUnit(100),
            'college_year_graduated' => $year,
            'graduate_school' => $schoolName(150),
            'graduate_degree_course' => $degreeCourse(150),
            'graduate_highest_level_unit' => $highestLevelUnit(100),
            'graduate_year_graduated' => $year,
            // 500 chars, not $optionalProse's usual 2000 - this is a short
            // packed list, not a paragraph.
            'scholarships_academic_honors' => [
                'nullable', 'string', 'max:500',
                'regex:/^[^<>{}|\\^~]*$/u',
                $scholarshipEntry,
            ],

            'sec_registration' => $code(100),
            'business_id_number' => $code(100),
            'dti_registration_number' => $code(100),
            'business_tin' => $idNumber(100),
            'non_academic_distinctions' => $optionalProse(2000),
            'membership_associations' => $optionalProse(2000),

            // Stamped by the controller on save — never typed, so it is not
            // validated as user input.
        ];
    }

    public function messages(): array
    {
        $messages = [
            // Personal information
            'surname.required' => 'Please enter your surname.',
            'surname.regex' => 'Surname can only contain letters, spaces, hyphens and periods.',
            'first_name.required' => 'Please enter your first name.',
            'first_name.regex' => 'First name can only contain letters, spaces, hyphens and periods.',
            'middle_name.required' => 'Please enter your middle name or N/A.',
            'middle_name.regex' => 'Middle name can only contain letters, spaces, hyphens and periods.',
            'name_extension.required' => 'Please enter your name extension or N/A.',
            'name_extension.regex' => 'Name extension can only contain letters and periods.',

            'height_input.required' => 'Please enter your height.',
            'height_input.regex' => 'Please enter a valid height.',
            'height_unit.required' => 'Choose cm, in, m or ft for the height.',
            'height_unit.in' => 'Choose cm, in, m or ft for the height.',
            'height_m.required' => 'Please enter your height.',
            'height_m.numeric' => 'Please enter a valid height.',
            'height_m.between' => 'Please enter a valid height.',

            'weight_input.required' => 'Please enter your weight.',
            'weight_input.regex' => 'Please enter a valid weight.',
            'weight_unit.required' => 'Choose kg or lb for the weight.',
            'weight_unit.in' => 'Choose kg or lb for the weight.',
            'weight_kg.required' => 'Please enter your weight.',
            'weight_kg.numeric' => 'Please enter a valid weight.',
            'weight_kg.between' => 'Please enter a valid weight.',
            'blood_type.required' => 'Please enter your blood type or N/A.',

            'gsis_no.required' => 'Please enter your GSIS ID number or N/A.',
            'gsis_no.regex' => 'Please enter a valid GSIS ID number.',
            'pagibig_no.required' => 'Please enter your PAG-IBIG number or N/A.',
            'pagibig_no.regex' => 'Please enter a valid PAG-IBIG number.',
            'philhealth_no.required' => 'Please enter your PhilHealth number or N/A.',
            'philhealth_no.regex' => 'Please enter a valid PhilHealth number.',
            'sss_no.required' => 'Please enter your SSS number or N/A.',
            'sss_no.regex' => 'Please enter a valid SSS number.',

            'residential_address.required' => 'Please enter your residential address.',
            'residential_address.regex' => 'Use letters, numbers and normal address punctuation only (. , - # / & ).',
            'residential_address.min' => 'Please enter your complete residential address.',
            'permanent_address.regex' => 'Use letters, numbers and normal address punctuation only (. , - # / & ).',
            'permanent_address.min' => 'Please enter your complete permanent address.',
            'blood_type.regex' => 'Enter a blood type such as O+, A-, AB+.',
            'sex.in' => 'Choose Male or Female.',
            'civil_status.in' => 'Choose one of the listed civil statuses.',
            'citizenship_by_birth.regex' => 'Use letters only, for example Filipino.',
            'citizenship_dual.regex' => 'Use letters only, or N/A if there is none.',
            'place_of_birth.regex' => 'Use letters, numbers, commas and periods only, for example Sta. Mesa, Manila.',
            'scholarships_academic_honors.regex' => 'Remove the < > { } | \\ ^ ~ characters.',
            'non_academic_distinctions.regex' => 'Please enter a valid distinction, recognition, or eligibility.',
            'membership_associations.regex' => 'Please enter a valid organization or association.',
            'startup_overview.regex' => 'Please enter a valid startup overview.',
            'permanent_address.required' => 'Please enter your permanent address.',
            'sex.required' => 'Please select your sex.',
            'civil_status.required' => 'Please select your civil status.',
            'citizenship_by_birth.required' => 'Please enter your citizenship.',
            'citizenship_dual.required' => 'Please enter your dual citizenship or N/A.',
            'place_of_birth.required' => 'Please enter your place of birth.',
            'date_of_birth.required' => 'Please enter your date of birth.',
            'date_of_birth.date' => 'Please enter a valid date of birth.',
            'date_of_birth.before' => 'Please enter a valid date of birth.',
            'date_of_birth.after' => 'Please enter a valid date of birth.',

            'mobile_no.required' => 'Please enter your mobile number or N/A.',
            'mobile_no.regex' => 'Please enter a valid mobile number (e.g., 09171234567).',
            'founder_email.required' => 'Please enter your email address.',
            'founder_email.email' => 'Please enter a valid email address.',

            // Business registration
            'sec_registration.required' => 'Enter the SEC registration number, or N/A if not registered.',
            'business_id_number.required' => 'Enter the business ID number, or N/A if there is none.',
            'dti_registration_number.required' => 'Enter the DTI registration number, or N/A if not registered.',
            'business_tin.required' => 'Enter the business TIN, or N/A if there is none.',
            'business_tin.regex' => 'Please enter a valid TIN.',
            'sec_registration.regex' => 'Please enter a valid SEC registration number.',
            'business_id_number.regex' => 'Please enter a valid business ID number.',
            'dti_registration_number.regex' => 'Please enter a valid DTI registration number.',

            // Long-form entries
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
            $messages[$key.'_school.required'] = 'Please enter the name of the school or N/A.';
            $messages[$key.'_degree_course.required'] = 'Please enter the degree/course or N/A.';
            $messages[$key.'_highest_level_unit.required'] = 'Please enter the highest level/unit or N/A.';
            $messages[$key.'_year_graduated.required'] = 'Please enter the year graduated or N/A.';
            $messages[$key.'_year_graduated.regex'] = "Year graduated for {$label} must be a 4-digit year, for example 2018.";
            $messages[$key.'_school.regex'] = "The {$label} name can only contain letters, numbers and . - ' & ( ) punctuation.";
            $messages[$key.'_degree_course.regex'] = "The {$label} degree or course can only contain letters, numbers and . - / & ( ) punctuation.";
            $messages[$key.'_highest_level_unit.regex'] = "The {$label} level or units can only contain letters, numbers and . - / ( ) punctuation.";
        }

        // Fallback for anything not named above.
        $messages['required'] = 'This field is required. Enter N/A if it does not apply.';

        return $messages;
    }

    public function attributes(): array
    {
        return [
            'height_m' => 'height',
            'height_input' => 'height',
            'height_unit' => 'height unit',
            'weight_kg' => 'weight',
            'weight_input' => 'weight',
            'weight_unit' => 'weight unit',
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
        ];
    }
}