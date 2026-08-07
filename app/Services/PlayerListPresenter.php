<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PlayerListPresenter
{
    private const ALLOWED_FILTERS = ['current_league', 'current_team', 'not_assigned'];

    public function validateListFilters(Request $request): ?string
    {
        $filter = $this->normalizeFilter($request->input('filter'));

        if ($filter === null) {
            return null;
        }

        if (! in_array($filter, self::ALLOWED_FILTERS, true)) {
            return 'Invalid filter. Allowed values: current_league, current_team, not_assigned.';
        }

        if ($filter === 'current_league' && ! $request->filled('league_id')) {
            return 'league_id is required when filter is current_league.';
        }

        if ($filter === 'current_team' && ! $request->filled('team_id')) {
            return 'team_id is required when filter is current_team.';
        }

        return null;
    }

    public function applyFilters(Builder $query, Request $request): void
    {
        $filter = $this->normalizeFilter($request->input('filter'));

        match ($filter) {
            'current_league' => $query->where(function (Builder $leagueQuery) use ($request) {
                $leagueId = (int) $request->input('league_id');

                $leagueQuery
                    ->where('players.league_id', $leagueId)
                    ->orWhereHas('teamPlayers', function (Builder $teamPlayerQuery) use ($leagueId) {
                        $teamPlayerQuery->whereHas('leagueTeam', function (Builder $leagueTeamQuery) use ($leagueId) {
                            $leagueTeamQuery->where('league_id', $leagueId);
                        });
                    });
            }),
            'current_team' => $query->whereHas('teamPlayers', function (Builder $teamPlayerQuery) use ($request) {
                $teamPlayerQuery->where('team_id', (int) $request->input('team_id'));
            }),
            'not_assigned' => $query->whereDoesntHave('teamPlayers'),
            default => null,
        };
    }

    /**
     * @param  iterable<Player>  $players
     * @return array<int, array<string, mixed>>
     */
    public function formatPlayers(iterable $players): array
    {
        $formatted = [];

        foreach ($players as $player) {
            $formatted[] = $this->formatPlayer($player);
        }

        return $formatted;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatPlayer(Player $player): array
    {
        $data = $player->toArray();
        unset($data['team_players'], $data['league']);

        $data['teams'] = $this->buildTeams($player);
        $data['leagues'] = $this->buildLeagues($player);

        return $data;
    }

    /**
     * @return array<int, array{team_id: int, team_name: string|null, league_id: int|null}>
     */
    private function buildTeams(Player $player): array
    {
        return $player->teamPlayers
            ->map(function ($teamPlayer) {
                return [
                    'team_id' => $teamPlayer->team_id,
                    'team_name' => $teamPlayer->leagueTeam?->team_name,
                    'league_id' => $teamPlayer->leagueTeam?->league_id,
                ];
            })
            ->filter(fn (array $team) => $team['team_id'] !== null)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{league_id: int, league_name: string|null}>
     */
    private function buildLeagues(Player $player): array
    {
        $leagues = collect();

        foreach ($player->teamPlayers as $teamPlayer) {
            $leagueId = $teamPlayer->leagueTeam?->league_id;
            if ($leagueId === null) {
                continue;
            }

            $leagues->put((int) $leagueId, [
                'league_id' => (int) $leagueId,
                'league_name' => $teamPlayer->leagueTeam?->league?->title,
            ]);
        }

        if ($player->league_id && ! $leagues->has((int) $player->league_id)) {
            $leagues->put((int) $player->league_id, [
                'league_id' => (int) $player->league_id,
                'league_name' => $player->league?->title,
            ]);
        }

        return $leagues->values()->all();
    }

    private function normalizeFilter(mixed $filter): ?string
    {
        if ($filter === null) {
            return null;
        }

        $filter = trim((string) $filter);

        return $filter === '' ? null : $filter;
    }
}
