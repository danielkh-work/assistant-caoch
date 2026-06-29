<?php

namespace App\Policies;

use App\Models\League;
use App\Models\User;

class LeaguePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, League $league): bool
    {
        return $league->isAccessibleBy($user);
    }

    public function create(User $user): bool
    {
        return $user->role === 'head_coach';
    }

    public function update(User $user, League $league): bool
    {
        return $league->isOwnedBy($user);
    }

    public function delete(User $user, League $league): bool
    {
        return $league->isOwnedBy($user);
    }

    public function restore(User $user, League $league): bool
    {
        return $league->isOwnedBy($user);
    }

    public function forceDelete(User $user, League $league): bool
    {
        return $league->isOwnedBy($user);
    }
}
