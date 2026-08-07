<?php

namespace App\Services;

use App\Models\TeamPlayer;
use Illuminate\Support\Collection;

class LeaguePlayerTeamValidator
{
    /**
     * Find players already rostered on another team in the same league.
     *
     * @param  int  $leagueId
     * @param  int  $currentTeamId  Team being updated (excluded from conflicts)
     * @param  array<int|string|null>  $playerIds
     */
    public function findConflicts(int $leagueId, int $currentTeamId, array $playerIds): Collection
    {
        $ids = collect($playerIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return collect();
        }

        return TeamPlayer::query()
            ->select([
                'team_players.player_id',
                'team_players.name as roster_name',
                'league_teams.id as team_id',
                'league_teams.team_name',
            ])
            ->join('league_teams', 'league_teams.id', '=', 'team_players.team_id')
            ->whereIn('team_players.player_id', $ids)
            ->where('league_teams.league_id', $leagueId)
            ->where('team_players.team_id', '!=', $currentTeamId)
            ->get();
    }

    public function conflictMessage(object $conflict): string
    {
        $playerName = $conflict->roster_name ?: 'This player';
        $teamName = $conflict->team_name ?: 'another team';

        return sprintf(
            'Player "%s" is already assigned to team "%s" in this league.',
            $playerName,
            $teamName
        );
    }

    /**
     * @return string|null Error message for the first conflict, or null if valid
     */
    public function firstConflictMessage(int $leagueId, int $currentTeamId, array $playerIds): ?string
    {
        $conflict = $this->findConflicts($leagueId, $currentTeamId, $playerIds)->first();

        return $conflict ? $this->conflictMessage($conflict) : null;
    }
}
