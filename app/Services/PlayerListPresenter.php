<?php

namespace App\Services;

use App\Models\League;
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

        if ($filter === 'current_league') {
            $leagueId = (int) $request->input('league_id');
            $isAccessible = League::query()
                ->visibleToUser(auth()->user())
                ->whereKey($leagueId)
                ->exists();

            if (! $isAccessible) {
                return 'You do not have access to this league.';
            }
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
    public function formatPlayers(iterable $players, ?Request $request = null): array
    {
        $scope = $this->resolveListScope($request);
        $formatted = [];

        foreach ($players as $player) {
            $formatted[] = $this->formatPlayer($player, $scope);
        }

        return $formatted;
    }

    /**
     * @param  array{league_id?: int, team_id?: int}  $scope
     * @return array<string, mixed>
     */
    public function formatPlayer(Player $player, array $scope = []): array
    {
        $data = $player->toArray();
        unset($data['team_players'], $data['league']);

        $data['teams'] = $this->buildTeams($player, $scope);
        $data['leagues'] = $this->buildLeagues($player, $scope);

        return $data;
    }

    /**
     * When a list filter is active, scope nested team/league metadata to that context.
     * Always intersect with leagues visible on the authenticated user's dashboard.
     *
     * @return array{league_id?: int, team_id?: int, accessible_league_ids?: array<int, int>}
     */
    private function resolveListScope(?Request $request): array
    {
        $scope = [];

        if ($request !== null) {
            $filter = $this->normalizeFilter($request->input('filter'));

            $scope = match ($filter) {
                'current_league' => ['league_id' => (int) $request->input('league_id')],
                'current_team' => ['team_id' => (int) $request->input('team_id')],
                default => [],
            };
        }

        $user = auth()->user();
        if ($user !== null) {
            $scope['accessible_league_ids'] = $this->resolveAccessibleLeagueIds($request, $user);
        }

        return $scope;
    }

    /**
     * Same league visibility rules as the dashboard: owned/shared leagues,
     * optionally limited to the sport of the current league/team context.
     *
     * @return array<int, int>
     */
    private function resolveAccessibleLeagueIds(?Request $request, $user): array
    {
        $query = League::query()->visibleToUser($user);

        $sportId = $this->resolveContextSportId($request, $user);
        if ($sportId !== null) {
            $query->where('sport_id', $sportId);
        }

        return $query
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function resolveContextSportId(?Request $request, $user): ?int
    {
        if ($request === null) {
            return null;
        }

        if ($request->filled('sport_id')) {
            return (int) $request->input('sport_id');
        }

        if ($request->filled('league_id')) {
            $sportId = League::query()
                ->visibleToUser($user)
                ->whereKey((int) $request->input('league_id'))
                ->value('sport_id');

            return $sportId !== null ? (int) $sportId : null;
        }

        if ($request->filled('team_id')) {
            $sportId = League::query()
                ->visibleToUser($user)
                ->whereHas('teams', function (Builder $teamQuery) use ($request) {
                    $teamQuery->whereKey((int) $request->input('team_id'));
                })
                ->value('sport_id');

            return $sportId !== null ? (int) $sportId : null;
        }

        return null;
    }

    /**
     * @param  array{league_id?: int, team_id?: int, accessible_league_ids?: array<int, int>}  $scope
     * @return array<int, array{team_id: int, team_name: string|null, league_id: int|null}>
     */
    private function buildTeams(Player $player, array $scope = []): array
    {
        $teams = $player->teamPlayers
            ->map(function ($teamPlayer) {
                return [
                    'team_id' => $teamPlayer->team_id,
                    'team_name' => $teamPlayer->leagueTeam?->team_name,
                    'league_id' => $teamPlayer->leagueTeam?->league_id,
                ];
            })
            ->filter(fn (array $team) => $team['team_id'] !== null && $team['league_id'] !== null);

        $teams = $this->applyLeagueScope($teams, $scope);

        if (isset($scope['team_id'])) {
            $teamId = $scope['team_id'];
            $teams = $teams->filter(
                fn (array $team) => (int) $team['team_id'] === $teamId
            );
        }

        return $teams
            ->unique(fn (array $team) => (int) $team['team_id'].'-'.(int) $team['league_id'])
            ->values()
            ->all();
    }

    /**
     * @param  array{league_id?: int, team_id?: int, accessible_league_ids?: array<int, int>}  $scope
     * @return array<int, array{league_id: int, league_name: string|null}>
     */
    private function buildLeagues(Player $player, array $scope = []): array
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

        $leagues = $this->applyLeagueScope($leagues, $scope);

        if (isset($scope['team_id'])) {
            $teamLeagueId = $player->teamPlayers
                ->first(fn ($teamPlayer) => (int) $teamPlayer->team_id === $scope['team_id'])
                ?->leagueTeam
                ?->league_id;

            if ($teamLeagueId !== null) {
                $leagues = $leagues->filter(
                    fn (array $league) => (int) $league['league_id'] === (int) $teamLeagueId
                );
            }
        }

        return $leagues->values()->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $items
     * @param  array{league_id?: int, accessible_league_ids?: array<int, int>}  $scope
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function applyLeagueScope($items, array $scope)
    {
        if (isset($scope['league_id'])) {
            $leagueId = $scope['league_id'];
            $items = $items->filter(
                fn (array $row) => (int) ($row['league_id'] ?? 0) === $leagueId
            );
        }

        if (! empty($scope['accessible_league_ids'])) {
            $allowed = $scope['accessible_league_ids'];
            $items = $items->filter(
                fn (array $row) => in_array((int) ($row['league_id'] ?? 0), $allowed, true)
            );
        }

        return $items;
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
