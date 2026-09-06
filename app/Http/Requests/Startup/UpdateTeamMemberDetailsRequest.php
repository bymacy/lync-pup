<?php

namespace App\Http\Requests\Startup;

/**
 * The saved Core Team rows on the Information Sheet PATCH through this. Same
 * every-column-required rules as adding a new row, so a row cannot be saved
 * complete and then emptied out one cell at a time.
 *
 * full_name is the one exception: this is the only endpoint that enforces
 * the exact "Surname, Firstname[, Middle Name[, Ext]]" shape the Information
 * Sheet's own Core Team Formation column header asks for (rowFullName, not
 * the looser rowPersonName the base class uses). Adding or editing a team
 * member from the Startup Profile side (StoreTeamMemberRequest /
 * UpdateTeamMemberRequest) stays as-is - that screen just asks for a "Full
 * Name" with no comma-format hint - so this override, not a change to the
 * shared base class, is what keeps the two screens' expectations honest.
 */
class UpdateTeamMemberDetailsRequest extends StoreTeamMemberRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'full_name' => $this->rowFullName(150),
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'full_name.regex' => 'Enter the name as Surname, Firstname — optionally followed by Middle Name and/or Ext, e.g. "Dela Cruz, Juan" or "Dela Cruz, Juan, Santos, Jr." Leave out Middle Name/Ext entirely if there is none - do not type N/A.',
        ]);
    }
}
