<?php

namespace App\Http\Requests\Startup;

/**
 * Shared column shapes for the Information Sheet's row tables — Core Team
 * Formation, Incubation Involvement, L&D Interventions and References.
 *
 * Every column is required, exactly like the rest of PUP-TBIDO Form No. 001:
 * the form says "Indicate N/A If Not Applicable", so a blank cell means
 * "unanswered", not "does not apply". Columns that hold a real type — dates,
 * email, phone, hours — still have to carry a valid value, since "N/A" in
 * those would break the PDF exports and downstream parsing.
 *
 * A row that is entirely blank never reaches here: the founder-side page drops
 * empty "add new" rows before saving (see submitInfoSheetForms()).
 */
trait SheetRowRules
{
    /**
     * Trim each cell and fold a pasted line break into a space. The row inputs
     * block Enter, but a paste can still smuggle one in.
     */
    protected function prepareForValidation(): void
    {
        $payload = [];

        foreach ($this->all() as $field => $value) {
            if (! is_string($value) || in_array($field, ['_token', '_method'], true)) {
                continue;
            }

            $clean = preg_replace('/\s*\R\s*/u', ' ', trim($value));

            if ($clean !== $value) {
                $payload[$field] = $clean;
            }
        }

        if ($payload) {
            $this->merge($payload);
        }
    }

    /** Letters only, plus the punctuation real names carry. */
    protected function rowName(int $max): array
    {
        return ['required', 'string', 'max:'.$max, 'regex:/^(n\/a|[\p{L}][\p{L}\s\.\,\-\x{2019}\']*)$/iu'];
    }

    /** Words, digits and ordinary punctuation — organisations, titles, addresses. */
    protected function rowText(int $max): array
    {
        return ['required', 'string', 'max:'.$max, 'regex:/^(n\/a|[\p{L}\p{N}\#][\p{L}\p{N}\s\.\,\-\#\/\(\)\&\x{2019}\']*)$/iu'];
    }

    /** Never-numeric answers: sex, civil status, citizenship. */
    protected function rowWords(int $max): array
    {
        return ['required', 'string', 'max:'.$max, 'regex:/^(n\/a|[\p{L}][\p{L}\s\.\-\x{2019}\']*)$/iu'];
    }

    /** A count of hours, or N/A. */
    protected function rowHours(): array
    {
        return ['required', 'string', 'max:20', 'regex:/^(n\/a|\d+(\.\d+)?)$/i'];
    }

    /** Same PH mobile shape the founder's own contact number uses. */
    protected function rowPhone(): array
    {
        return ['required', 'string', 'max:20', 'regex:/^(\+63|0)9\d{2}[ -]?\d{3}[ -]?\d{4}$/'];
    }

    /**
     * A real person's name: letters, spaces, and . - ' only - no digits, no
     * N/A (unlike rowName() above, which is shared with rows that may
     * genuinely have nothing to put there). Suffixes like "Jr." or "III"
     * are just letters and a period, so they already fit.
     */
    protected function rowPersonName(int $max): array
    {
        return ['required', 'string', 'max:'.$max, 'regex:/^[\p{L}][\p{L}\s\.\-\x{2019}\']*$/iu'];
    }

    /** A job title or role: letters, numbers, spaces, and . - / & - no N/A. */
    protected function rowDesignation(int $max): array
    {
        return ['required', 'string', 'max:'.$max, 'regex:/^[\p{L}\p{N}][\p{L}\p{N}\s\.\-\/\&]*$/iu'];
    }

    /** A real address: letters, numbers, spaces, and , . - # / - no N/A. */
    protected function rowAddress(int $max): array
    {
        return ['required', 'string', 'max:'.$max, 'regex:/^[\p{L}\p{N}\#][\p{L}\p{N}\s\.\,\-\#\/]*$/iu'];
    }

    /** A citizenship/nationality: letters, spaces, and . - ' only - no N/A. */
    protected function rowCitizenship(int $max): array
    {
        return ['required', 'string', 'max:'.$max, 'regex:/^[\p{L}][\p{L}\s\.\-\x{2019}\']*$/iu'];
    }

    /**
     * Fallback wording, so a column without its own message still tells the
     * founder what to do rather than showing Laravel's default.
     */
    protected function rowMessages(array $messages = []): array
    {
        return $messages + [
            'required' => 'Required. Type N/A if it does not apply.',
            'regex' => 'Check this entry — it contains characters that are not allowed here.',
            'date' => 'Enter a valid date.',
            'email' => 'Enter a valid email address, for example name@email.com.',
        ];
    }
}
