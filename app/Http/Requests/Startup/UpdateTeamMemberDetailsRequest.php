<?php

namespace App\Http\Requests\Startup;

/**
 * The saved Core Team rows on the Information Sheet PATCH through this. Same
 * every-column-required rules as adding a new row (StoreTeamMemberRequest,
 * which the base class already inherits - both the "+ Add Entry" store
 * endpoint and this update-details endpoint enforce full_name's exact
 * "Surname, Firstname[, Middle Name[, Ext]]" shape now, so a row can't be
 * saved in that format and then edited into something looser, or vice
 * versa). The rules() override below is a no-op now that the parent already
 * uses rowFullName - kept only so this class stays explicit about the shape
 * it depends on. No messages() override anymore: the parent's full_name.regex
 * message is already short enough to just inherit as-is.
 */
class UpdateTeamMemberDetailsRequest extends StoreTeamMemberRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'full_name' => $this->rowFullName(150),
        ]);
    }
}
