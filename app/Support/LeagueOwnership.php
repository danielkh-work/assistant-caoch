<?php

namespace App\Support;

use App\Models\League;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

class LeagueOwnership
{
    public static function resolveHeadCoachId(?User $user = null): int
    {
        $user ??= auth()->user();

        if ($user->role === 'head_coach') {
            return (int) $user->id;
        }

        if ($user->head_coach_id) {
            return (int) $user->head_coach_id;
        }

        abort(403, 'Head coach context is required.');
    }

    public static function leagueForHeadCoach(int $leagueId, ?User $user = null): League
    {
        $user ??= auth()->user();
        $headCoachId = self::resolveHeadCoachId($user);

        return League::query()
            ->whereKey($leagueId)
            ->where('user_id', $headCoachId)
            ->firstOrFail();
    }

    public static function qbForLeague(int $leagueId, ?User $user = null): ?User
    {
        $headCoachId = self::resolveHeadCoachId($user);

        return User::query()
            ->where('role', 'qb')
            ->where('head_coach_id', $headCoachId)
            ->where('league_id', $leagueId)
            ->first();
    }
}
