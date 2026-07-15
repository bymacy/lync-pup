<?php

namespace App\Policies;

use App\Models\Startup;
use App\Models\User;

class StartupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Startup $startup): bool
    {
        return $user->isAdmin() || $user->id === $startup->user_id;
    }
}