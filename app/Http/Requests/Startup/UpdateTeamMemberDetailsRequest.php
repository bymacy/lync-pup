<?php

namespace App\Http\Requests\Startup;

/**
 * The saved Core Team rows on the Information Sheet PATCH through this. Same
 * every-column-required rules as adding a new row, so a row cannot be saved
 * complete and then emptied out one cell at a time.
 */
class UpdateTeamMemberDetailsRequest extends StoreTeamMemberRequest
{
}
