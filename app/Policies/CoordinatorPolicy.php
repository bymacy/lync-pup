<?php

namespace App\Policies;

use App\Models\Coordinator;
use App\Models\User;

class CoordinatorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Coordinator $coordinator): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Coordinator $coordinator): bool
    {
        return $user->isAdmin();
    }
}