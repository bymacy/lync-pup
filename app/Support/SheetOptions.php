<?php

namespace App\Support;

/**
 * Option lists for the Information Sheet's dropdown fields.
 *
 * Values are stored uppercase because the sheet is upper-cased on save (see
 * the UpdateInformationSheetRequest classes), so the option a founder picks
 * matches what is written to the column byte for byte.
 *
 * "N/A" is included everywhere: every field on the sheet is required, and N/A
 * is the accepted way to say "does not apply".
 */
class SheetOptions
{
    /** Metres, 1.30 - 2.00. */
    public static function heights(): array
    {
        $values = [];

        for ($cm = 130; $cm <= 200; $cm++) {
            $values[] = number_format($cm / 100, 2);
        }

        return array_merge($values, ['N/A']);
    }

    /** Kilograms, 30 - 150. */
    public static function weights(): array
    {
        $values = [];

        for ($kg = 30; $kg <= 150; $kg++) {
            $values[] = (string) $kg;
        }

        return array_merge($values, ['N/A']);
    }

    public static function bloodTypes(): array
    {
        return ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'N/A'];
    }

    public static function sexes(): array
    {
        return ['FEMALE', 'MALE'];
    }

    /**
     * The standard Philippine civil-status set. Divorce is not recognised
     * locally, so "ANNULLED" covers that case instead.
     */
    public static function civilStatuses(): array
    {
        return ['SINGLE', 'MARRIED', 'WIDOWED', 'SEPARATED', 'ANNULLED'];
    }

    /** Graduation years, newest first, back to 1960. */
    public static function years(): array
    {
        $years = range((int) date('Y'), 1960);

        return array_merge(array_map('strval', $years), ['N/A']);
    }

    /** "Highest level / units earned" on the educational background table. */
    public static function educationLevels(): array
    {
        return [
            'GRADUATED',
            'UNDERGRADUATE',
            '1ST YEAR',
            '2ND YEAR',
            '3RD YEAR',
            '4TH YEAR',
            '5TH YEAR',
            'WITH UNITS EARNED',
            'N/A',
        ];
    }
}
