<?php

namespace App\Support;

use App\Models\League;
use App\Models\LeagueTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

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
        return League::query()->whereKey($leagueId)->firstOrFail();
    }

    public static function assertLeagueOwnedByHeadCoach(League $league, ?User $user = null): void
    {
        $user ??= auth()->user();
        $headCoachId = self::resolveHeadCoachId($user);

        if ((int) $league->user_id !== $headCoachId) {
            abort(403, 'You do not have access to manage this league.');
        }
    }

    public static function teamForLeague(int $teamId, int $leagueId, ?User $user = null): LeagueTeam
    {
        self::leagueForHeadCoach($leagueId, $user);

        return LeagueTeam::query()
            ->whereKey($teamId)
            ->where('league_id', $leagueId)
            ->firstOrFail();
    }

    public static function qbForLeagueTeam(int $leagueId, int $teamId, ?User $user = null): ?User
    {
        $headCoachId = self::resolveHeadCoachId($user);

        return User::query()
            ->where('role', 'qb')
            ->where('head_coach_id', $headCoachId)
            ->where('league_id', $leagueId)
            ->where('team_id', $teamId)
            ->first();
    }

    /**
     * @return Collection<int, User>
     */
    public static function qbsForLeague(int $leagueId, ?User $user = null): Collection
    {
        $headCoachId = self::resolveHeadCoachId($user);

        return User::query()
            ->where('role', 'qb')
            ->where('head_coach_id', $headCoachId)
            ->where('league_id', $leagueId)
            ->get();
    }
}
