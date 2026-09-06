<?php

namespace App\Http\Requests\Admin;

class UpdateCohortRequest extends StoreCohortRequest
{
    // Same rules as Store — the unique label check already excludes the
    // current cohort's own row via the route-bound {cohort} in rules().
}
