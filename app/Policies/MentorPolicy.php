<?php

namespace App\Policies;

use App\Models\Mentor;
use App\Models\User;

class MentorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Mentor $mentor): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Mentor $mentor): bool
    {
        return $user->isAdmin();
    }
}