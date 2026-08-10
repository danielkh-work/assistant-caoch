<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\BenchPlayer;
use App\Models\League;
use App\Models\LeagueTeam;
use App\Models\PersionalGrouping;
use Illuminate\Http\Request;
use App\Http\Responses\BaseResponse;
use App\Models\Penality;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GameController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'league_id' => 'required|integer',
            'my_team_id' => 'required|integer',
            'oponent_team_id' => 'required|integer',
            'date' => 'required|date|after_or_equal:today',
            'location' => 'nullable',
            'neutral_location'  => 'nullable',
            'location_type'   => 'required|string|in:home,visiting,neutral',
        ]);

        $validated['date'] = Carbon::parse($validated['date'])->format('Y-m-d H:i:00');

        $conflict = $this->leagueDateTimeConflictResponse(
            (int) $validated['league_id'],
            $validated['date']
        );
        if ($conflict) {
            return $conflict;
        }

        $teamError = $this->leagueTeamIntegrityResponse(
            (int) $validated['league_id'],
            (int) $validated['my_team_id'],
            (int) $validated['oponent_team_id']
        );
        if ($teamError) {
            return $teamError;
        }

        $validated['creator_id']= auth()->user()->id;
        $game = Game::create($validated);

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player Added SuccessFully ", $game);
    }

    public function index() {
        $games = Game::with(['myTeam', 'opponentTeam'])->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "games list", $games);
    }
    public function show($id)
    {
        $game = Game::with(['myTeam.teamplayer.player', 'opponentTeam.teamplayer.player'])->findOrFail($id);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "games", $game);
    }
    public function getOpponentMyTeamPlayers($id)
    {
        $game = Game::with(['configureMyTeams.player.player', 'configureVisitingTeams.player.player'])->findOrFail($id);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "games", $game);
    }

    public function opponentTeams(Request $request, $leagueId)
    {
        $search = trim((string) $request->query('search', ''));
        $excludeTeamId = $request->query('my_team_id', $request->query('exclude_team_id'));
        if (($excludeTeamId === null || $excludeTeamId === '') && Schema::hasColumn('league_teams', 'type')) {
            $excludeTeamId = $this->resolveMyTeamIdForLeague((int) $leagueId);
        }

        $query = LeagueTeam::query()
            ->where('league_id', $leagueId)
            ->select('id', 'team_name')
            ->orderBy('team_name')
            ->limit(300);

        if ($excludeTeamId !== null && $excludeTeamId !== '') {
            $query->whereKeyNot((int) $excludeTeamId);
        }

        if (Schema::hasColumn('league_teams', 'is_practice')) {
            $query->where(function ($q) {
                $q->where('is_practice', 0)
                    ->orWhereNull('is_practice');
            });
        }

        if ($search !== '') {
            $needle = '%' . addcslashes($search, '%_\\') . '%';
            $query->where('team_name', 'like', $needle);
        }

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'Opponent teams list',
            $query->get()
        );
    }

    private function resolveMyTeamIdForLeague(int $leagueId): ?int
    {
        $query = LeagueTeam::query()
            ->where('league_id', $leagueId)
            ->where('type', 1)
            ->orderBy('id');

        if (Schema::hasColumn('league_teams', 'is_practice')) {
            $query->where(function ($q) {
                $q->where('is_practice', 0)
                    ->orWhereNull('is_practice');
            });
        }

        $teamId = $query->value('id');

        return $teamId ? (int) $teamId : null;
    }

    private function leagueTeamExistsForGame(int $leagueId, int $teamId): bool
    {
        $query = LeagueTeam::query()
            ->whereKey($teamId)
            ->where('league_id', $leagueId);

        if (Schema::hasColumn('league_teams', 'is_practice')) {
            $query->where(function ($q) {
                $q->where('is_practice', 0)
                    ->orWhereNull('is_practice');
            });
        }

        return $query->exists();
    }

    private function leagueTeamIntegrityResponse(
        int $leagueId,
        int $myTeamId,
        int $opponentTeamId
    ): ?BaseResponse {
        if ($myTeamId === $opponentTeamId) {
            return new BaseResponse(
                STATUS_CODE_UNPROCESSABLE,
                STATUS_CODE_UNPROCESSABLE,
                'My team and opponent team must be different.'
            );
        }

        foreach ([$myTeamId, $opponentTeamId] as $teamId) {
            if (! $this->leagueTeamExistsForGame($leagueId, $teamId)) {
                return new BaseResponse(
                    STATUS_CODE_UNPROCESSABLE,
                    STATUS_CODE_UNPROCESSABLE,
                    'Selected team is invalid for this league.'
                );
            }
        }

        return null;
    }

    private function applyLeagueTeamIntegrityFilter(Builder $query, int $leagueId): Builder
    {
        return $query
            ->whereHas('myTeam', fn (Builder $teamQuery) => $teamQuery->where('league_id', $leagueId))
            ->whereHas('opponentTeam', fn (Builder $teamQuery) => $teamQuery->where('league_id', $leagueId));
    }

    private function mapUpcomingMatchSummaries($games)
    {
        return collect($games)->map(function (Game $game) {
            return [
                'id' => $game->id,
                'date' => $game->date,
                'status' => $game->status,
                'match_start_date' => $game->match_start_date,
                'match_end_date' => $game->match_end_date,
                'my_team_id' => $game->my_team_id,
                'my_team_name' => optional($game->myTeam)->team_name,
                'opponent_team_id' => $game->oponent_team_id,
                'opponent_team_name' => optional($game->opponentTeam)->team_name,
                'location' => $game->location,
                'location_type' => $game->location_type,
                'neutral_location' => $game->neutral_location,
            ];
        });
    }

    /**
     * League-scoped uniqueness: one game per calendar day (soft-deleted games ignored).
     */
    private function leagueDateTimeConflictResponse(
        int $leagueId,
        string $date,
        ?int $ignoreGameId = null
    ): ?BaseResponse {
        $query = Game::query()
            ->where('league_id', $leagueId)
            ->whereDate('date', Carbon::parse($date)->toDateString());

        if ($ignoreGameId !== null) {
            $query->whereKeyNot($ignoreGameId);
        }

        if ($query->exists()) {
            return new BaseResponse(
                STATUS_CODE_UNPROCESSABLE,
                STATUS_CODE_UNPROCESSABLE,
                'A game is already scheduled on this date.'
            );
        }

        return null;
    }

    public function duplicate(Request $request, $id)
    {
        $request->validate([
            'date' => 'sometimes|nullable|date|after_or_equal:today',
            'opponent_team_id' => 'sometimes|nullable|integer',
            'oponent_team_id' => 'sometimes|nullable|integer',
        ]);

        $game = Game::find($id);
        if (! $game) {
            return new BaseResponse(STATUS_CODE_NOTFOUND, STATUS_CODE_NOTFOUND, 'Game not found.');
        }

        $newOpponentTeamId = $request->input('opponent_team_id', $request->input('oponent_team_id', $game->oponent_team_id));
        $newOpponentTeamId = $newOpponentTeamId !== null && $newOpponentTeamId !== ''
            ? (int) $newOpponentTeamId
            : (int) $game->oponent_team_id;

        if ($newOpponentTeamId !== (int) $game->oponent_team_id
            && ! $this->leagueTeamExistsForGame((int) $game->league_id, $newOpponentTeamId)) {
            return new BaseResponse(
                STATUS_CODE_UNPROCESSABLE,
                STATUS_CODE_UNPROCESSABLE,
                'Selected opponent team is invalid for this league.'
            );
        }

        $opponentChanged = $newOpponentTeamId !== (int) $game->oponent_team_id;

        $resolvedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->format('Y-m-d H:i:00')
            : (string) $game->date;

        if ($resolvedDate !== '') {
            $conflict = $this->leagueDateTimeConflictResponse(
                (int) $game->league_id,
                $resolvedDate
            );
            if ($conflict) {
                return $conflict;
            }
        }

        DB::beginTransaction();
        try {
            $duplicate = $game->replicate();
            $duplicate->oponent_team_id = $newOpponentTeamId;

            if ($request->filled('date') && Schema::hasColumn('games', 'date')) {
                $duplicate->date = $resolvedDate;
            }

            if (Schema::hasColumn('games', 'creator_id') && auth()->id()) {
                $duplicate->creator_id = auth()->id();
            }

            foreach (['status', 'match_start_date', 'match_end_date'] as $column) {
                if (Schema::hasColumn('games', $column)) {
                    $duplicate->{$column} = null;
                }
            }

            $duplicate->save();

            $myTeamOnly = fn ($query) => $query->where('team_id', $game->my_team_id);

            $this->copyRowsForGame(
                'configured_playing_team_players',
                'match_id',
                $game->id,
                $duplicate->id,
                $opponentChanged ? $myTeamOnly : null
            );

            $this->copyRowsForGame('configure_plays', 'match_id', $game->id, $duplicate->id);

            if (! $opponentChanged) {
                $this->copyRowsForGame('configure_defensive_plays', 'game_id', $game->id, $duplicate->id);
            }

            $this->copyRowsForGame(
                'offense_defense_players',
                'game_id',
                $game->id,
                $duplicate->id,
                $opponentChanged ? $myTeamOnly : null
            );

            $groupIdMap = $this->copyRowsForGame(
                'personal_groupings',
                'game_id',
                $game->id,
                $duplicate->id,
                $opponentChanged ? $myTeamOnly : null
            );
            $this->copyPersonalGroupingPivots($groupIdMap);

            if (! $opponentChanged) {
                $packageIdMap = $this->copyRowsForGame('opponent_team_packages', 'game_id', $game->id, $duplicate->id);
                $this->copyOpponentPackagePlayers($packageIdMap);
            }

            DB::commit();

            $duplicate->load([
                'myTeam',
                'opponentTeam',
                'configuredPlays',
                'configureMyTeams',
                'configureVisitingTeams',
            ]);

            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, 'Game duplicated successfully.', $duplicate);
        } catch (\Throwable $th) {
            DB::rollBack();

            return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
        }
    }

    private function copyRowsForGame(
        string $table,
        string $gameColumn,
        int $sourceGameId,
        int $newGameId,
        ?callable $filter = null
    ): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $gameColumn)) {
            return [];
        }

        $now = now();
        $idMap = [];

        $query = DB::table($table)
            ->where($gameColumn, $sourceGameId);

        if ($filter !== null) {
            $filter($query);
        }

        $query->orderBy('id')
            ->get()
            ->each(function ($row) use ($table, $gameColumn, $newGameId, $now, &$idMap) {
                $data = (array) $row;
                $sourceId = $data['id'] ?? null;
                unset($data['id']);

                $data[$gameColumn] = $newGameId;

                if (Schema::hasColumn($table, 'created_at')) {
                    $data['created_at'] = $now;
                }

                if (Schema::hasColumn($table, 'updated_at')) {
                    $data['updated_at'] = $now;
                }

                $newId = DB::table($table)->insertGetId($data);
                if ($sourceId !== null) {
                    $idMap[(int) $sourceId] = (int) $newId;
                }
            });

        return $idMap;
    }

    private function copyPersonalGroupingPivots(array $groupIdMap): void
    {
        if ($groupIdMap === []) {
            return;
        }

        $this->copyPivotRows('personal_grouping_play', 'personal_grouping_id', $groupIdMap);
        $this->copyPivotRows('defensive_play_personal_grouping', 'personal_grouping_id', $groupIdMap);
    }

    private function copyOpponentPackagePlayers(array $packageIdMap): void
    {
        if ($packageIdMap === []) {
            return;
        }

        $this->copyPivotRows('opponent_package_player', 'opponent_team_package_id', $packageIdMap);
    }

    private function copyPivotRows(string $table, string $foreignKey, array $idMap): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $foreignKey)) {
            return;
        }

        $now = now();

        DB::table($table)
            ->whereIn($foreignKey, array_keys($idMap))
            ->orderBy('id')
            ->get()
            ->each(function ($row) use ($table, $foreignKey, $idMap, $now) {
                $data = (array) $row;
                unset($data['id']);

                $data[$foreignKey] = $idMap[(int) $row->{$foreignKey}];

                if (Schema::hasColumn($table, 'created_at')) {
                    $data['created_at'] = $now;
                }

                if (Schema::hasColumn($table, 'updated_at')) {
                    $data['updated_at'] = $now;
                }

                DB::table($table)->insert($data);
            });
    }

    public function getByLeague($leagueId)
    {
        $eagerLoad = [
            'myTeam',
            'opponentTeam',
            'configuredPlays',
            'configureMyTeams',
            'configureVisitingTeams',
        ];

        $gameType = request()->query('type');
        $statusFilter = strtolower(trim((string) request()->query('status', '')));
        $startDate = trim((string) request()->query('start_date', ''));
        $endDate = trim((string) request()->query('end_date', ''));
        $singleDate = trim((string) request()->query('date', ''));
        $datePattern = '/^\d{4}-\d{2}-\d{2}$/';

        if ($singleDate !== '' && preg_match($datePattern, $singleDate)) {
            $startDate = $singleDate;
            $endDate = $singleDate;
        }

        $hasDateFilter = ($startDate !== '' && preg_match($datePattern, $startDate))
            || ($endDate !== '' && preg_match($datePattern, $endDate));

        $page = max(1, (int) request()->input('page', 1));
        $perPage = max(1, min(100, (int) request()->input('per_page', 18)));

        if (! $hasDateFilter && $statusFilter !== 'not-ended') {
            return $this->getByLeagueDefaultFeed(
                (int) $leagueId,
                $eagerLoad,
                $gameType,
                $page,
                $perPage
            );
        }

        $gamesQuery = $this->buildLeagueGamesQuery(
            (int) $leagueId,
            $eagerLoad,
            $gameType,
            $statusFilter,
            $startDate,
            $endDate,
            $datePattern
        );

        $gamesQuery
            ->orderByRaw("CASE WHEN LOWER(COALESCE(status, '')) = 'ended' THEN 1 ELSE 0 END")
            ->orderBy('date')
            ->orderBy('id');

        $paginator = $gamesQuery->paginate($perPage, ['*'], 'page', $page);

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'games list',
            $paginator->items(),
            null,
            null,
            [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ]
        );
    }

    private function buildLeagueGamesQuery(
        int $leagueId,
        array $eagerLoad,
        $gameType,
        string $statusFilter,
        string $startDate,
        string $endDate,
        string $datePattern
    ): Builder {
        $gamesQuery = Game::with($eagerLoad)->where('league_id', $leagueId);
        $gamesQuery = $this->applyLeagueTeamIntegrityFilter($gamesQuery, $leagueId);

        if ($gameType !== null) {
            $gamesQuery->where('type', $gameType);
        }

        if ($statusFilter === 'not-ended') {
            $gamesQuery->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', 'ended');
            });
        }

        if ($startDate !== '' && preg_match($datePattern, $startDate)) {
            $gamesQuery->whereDate('date', '>=', $startDate);
        }

        if ($endDate !== '' && preg_match($datePattern, $endDate)) {
            $gamesQuery->whereDate('date', '<=', $endDate);
        }

        return $gamesQuery;
    }

    /**
     * Default games feed: upcoming first (6–9 on page 1, then up to 9 per page),
     * then ended (3–6 on page 1, then up to 6 per page). Latest ended first.
     */
    private function getByLeagueDefaultFeed(
        int $leagueId,
        array $eagerLoad,
        $gameType,
        int $page,
        int $perPage
    ): BaseResponse {
        $baseQuery = $this->buildLeagueGamesQuery(
            $leagueId,
            $eagerLoad,
            $gameType,
            '',
            '',
            '',
            '/^\d{4}-\d{2}-\d{2}$/'
        );

        $upcomingQuery = (clone $baseQuery)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereRaw("LOWER(COALESCE(status, '')) != 'ended'");
            })
            ->orderBy('date')
            ->orderBy('id');

        $endedQuery = (clone $baseQuery)
            ->whereRaw("LOWER(COALESCE(status, '')) = 'ended'")
            ->orderByDesc('date')
            ->orderByDesc('id');

        $upcomingTotal = (clone $upcomingQuery)->count();
        $endedTotal = (clone $endedQuery)->count();
        $total = $upcomingTotal + $endedTotal;

        $window = $this->resolveDefaultGamesWindow(
            $page,
            $perPage,
            $upcomingTotal,
            $endedTotal
        );

        $items = collect();

        if ($window['upcoming_take'] > 0) {
            $items = $items->concat(
                (clone $upcomingQuery)
                    ->skip($window['upcoming_offset'])
                    ->take($window['upcoming_take'])
                    ->get()
            );
        }

        if ($window['ended_take'] > 0) {
            $items = $items->concat(
                (clone $endedQuery)
                    ->skip($window['ended_offset'])
                    ->take($window['ended_take'])
                    ->get()
            );
        }

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'games list',
            $items->values()->all(),
            null,
            null,
            [
                'total' => $total,
                'current_page' => $page,
                'per_page' => $items->count(),
                'last_page' => max(1, $window['last_page']),
            ]
        );
    }

    /**
     * @return array{
     *     upcoming_offset: int,
     *     upcoming_take: int,
     *     ended_offset: int,
     *     ended_take: int,
     *     last_page: int
     * }
     */
    private function resolveDefaultGamesWindow(
        int $page,
        int $perPage,
        int $upcomingTotal,
        int $endedTotal
    ): array {
        $upcomingMin = 6;
        $upcomingMax = 9;
        $endedMin = 3;
        $endedMax = 6;

        $upcomingOffset = 0;
        $endedOffset = 0;
        $lastPage = 0;
        $targetPageWindow = [
            'upcoming_offset' => 0,
            'upcoming_take' => 0,
            'ended_offset' => 0,
            'ended_take' => 0,
            'last_page' => 1,
        ];

        while ($upcomingOffset < $upcomingTotal || $endedOffset < $endedTotal) {
            $lastPage++;

            if ($lastPage === 1) {
                $upcomingTake = min($upcomingMax, $upcomingTotal - $upcomingOffset);
                if ($upcomingTotal >= $upcomingMin) {
                    $upcomingTake = max($upcomingMin, $upcomingTake);
                }

                $endedTake = min($endedMax, $endedTotal - $endedOffset);
                if ($endedTotal >= $endedMin) {
                    $endedTake = max($endedMin, $endedTake);
                }
            } elseif ($upcomingOffset < $upcomingTotal) {
                $upcomingTake = min($upcomingMax, $upcomingTotal - $upcomingOffset, $perPage);
                $endedTake = 0;
            } else {
                $upcomingTake = 0;
                $endedTake = min($endedMax, $endedTotal - $endedOffset, $perPage);
            }

            if ($lastPage === $page) {
                $targetPageWindow = [
                    'upcoming_offset' => $upcomingOffset,
                    'upcoming_take' => $upcomingTake,
                    'ended_offset' => $endedOffset,
                    'ended_take' => $endedTake,
                    'last_page' => $lastPage,
                ];
            }

            $upcomingOffset += $upcomingTake;
            $endedOffset += $endedTake;
        }

        if ($lastPage === 0) {
            $lastPage = 1;
        }

        if ($page > $lastPage) {
            return [
                'upcoming_offset' => $upcomingTotal,
                'upcoming_take' => 0,
                'ended_offset' => $endedTotal,
                'ended_take' => 0,
                'last_page' => $lastPage,
            ];
        }

        $targetPageWindow['last_page'] = $lastPage;

        return $targetPageWindow;
    }

    public function scheduledDatesByLeague(Request $request, $leagueId)
    {
        $league = League::select('id', 'title')->find($leagueId);

        if (! $league) {
            return new BaseResponse(STATUS_CODE_NOTFOUND, STATUS_CODE_NOTFOUND, 'League not found.');
        }

        $today = Carbon::today()->toDateString();
        $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
        $startDate = trim((string) $request->query('start_date', ''));
        $endDate = trim((string) $request->query('end_date', ''));

        $effectiveStart = ($startDate !== '' && preg_match($datePattern, $startDate) && $startDate >= $today)
            ? $startDate
            : $today;

        $datesQuery = $this->applyLeagueTeamIntegrityFilter(
            Game::query()->where('league_id', $leagueId),
            (int) $leagueId
        )
            ->where('type', 1)
            ->whereNotNull('date')
            ->whereDate('date', '>=', $effectiveStart);

        if ($endDate !== '' && preg_match($datePattern, $endDate)) {
            $datesQuery->whereDate('date', '<=', $endDate);
        }

        $dates = $datesQuery
            ->orderBy('date')
            ->get(['date'])
            ->map(fn (Game $game) => Carbon::parse($game->date)->format('Y-m-d'))
            ->unique()
            ->sort()
            ->values();

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'League scheduled dates',
            [
                'league_id' => $league->id,
                'league_name' => $league->title,
                'dates_count' => $dates->count(),
                'dates' => $dates,
            ]
        );
    }

    public function upcomingMatchesByLeague($leagueId)
    {
        $league = League::select('id', 'title')->find($leagueId);

        if (! $league) {
            return new BaseResponse(STATUS_CODE_NOTFOUND, STATUS_CODE_NOTFOUND, 'League not found.');
        }

        $matches = $this->mapUpcomingMatchSummaries(
            $this->applyLeagueTeamIntegrityFilter(
                Game::with([
                    'myTeam:id,team_name',
                    'opponentTeam:id,team_name',
                ])->where('league_id', $leagueId),
                (int) $leagueId
            )
                ->where('type', 1)
                ->where('date', '>=', now())
                ->whereNull('status')
                ->whereNull('match_start_date')
                ->whereNull('match_end_date')
                ->orderBy('date')
                ->orderBy('id')
                ->get()
        );

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'Upcoming real matches list',
            [
                'league_id' => $league->id,
                'league_name' => $league->title,
                'matches_count' => $matches->count(),
                'matches' => $matches,
            ]
        );
    }

    public function leaguesUpcomingMatches()
    {
        $user = auth()->user();

        $leagues = League::visibleToUser($user)
            ->select('id', 'title')
            ->get();

        $leaguesWithMatches = $leagues->map(function ($league) {
            $matches = $this->mapUpcomingMatchSummaries(
                $this->applyLeagueTeamIntegrityFilter(
                    Game::with([
                        'myTeam:id,team_name',
                        'opponentTeam:id,team_name',
                    ])->where('league_id', $league->id),
                    (int) $league->id
                )
                    ->where('type', 1)
                    ->where('date', '>=', now())
                    ->whereNull('status')
                    ->whereNull('match_start_date')
                    ->whereNull('match_end_date')
                    ->orderBy('date')
                    ->orderBy('id')
                    ->limit(1)
                    ->get()
            )
                ->values()
                ->all();

            return [
                'league_id' => $league->id,
                'league_name' => $league->title,
                'upcoming_matches' => $matches,
            ];
        });

        // Sort leagues by soonest upcoming match date (leagues with no matches go last)
        $leaguesWithMatches = $leaguesWithMatches->sortBy(function ($item) {
            if (empty($item['upcoming_matches'])) {
                return PHP_INT_MAX;
            }
            return $item['upcoming_matches'][0]['date'] ?? PHP_INT_MAX;
        })->values();

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'User leagues with upcoming matches',
            $leaguesWithMatches
        );
    }

    public function Penalities(Request $request)
    {
          $validated = $request->validate([
            'league_id'             => 'required|exists:leagues,id',
            'game_id'              => 'required',
            'penalty_type_id'       => 'required',
            'category'              => 'nullable',
            'severity'              => 'nullable',
            'yardage_penalty'       => 'nullable',
            'automatic_first_down'  => 'nullable',
            'loss_down'             => 'nullable',
            'accept_reject'         => 'nullable',
            'replay_down'           => 'nullable',
            'new_down'              => 'nullable',
            'new_ball_sport'        => 'nullable',
            'play_time'             => 'nullable',
            'setuation'             => 'nullable',
            'referee'               => 'nullable',
            'notes_description'     => 'nullable',
        ]);

        // ✅ Store penalty
        $penalty = Penality::create([
            'league_id'             => $validated['league_id'],
            'game_id'              => $validated['game_id'],
            'penalty_type_id'       => $validated['penalty_type_id'],
            'category'              => $validated['category'] ?? null,
            'severity'              => $validated['severity'] ?? null,
            'yardage_penalty'       => $validated['yardage_penalty'] ?? null,
            'automatic_first_down'  => $validated['automatic_first_down'] ?? null,
            'loss_down'             => $validated['loss_down'] ?? null,
            'accept_reject'         => $validated['accept_reject'] ?? null,
            'replay_down'           => $validated['replay_down'] ?? null,
            'new_down'              => $validated['new_down'] ?? null,
            'new_ball_sport'        => $validated['new_ball_sport'] ?? null,
            'play_time'             => $validated['play_time'] ?? null,
            'setuation'             => $validated['setuation'] ?? null,
            'referee'               => $validated['referee'] ?? null,
            'notes_description'     => $validated['notes_description'] ?? null,
        ]);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "'Penalty created successfully", $penalty);
    }

    public function penaltyList(Request $request)
    {
       $penalties = Penality::where([
        'league_id' => $request->league_id,
        'game_id'   => $request->game_id,
        ])
        ->orderBy('id', 'desc') // or ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($penalty) {
            // Format created_at as American 12-hour time
            $penalty->time_only = $penalty->created_at->format('h:i A');
            return $penalty;
        });
       return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "'Penalty List", $penalties);

    }


     public function delete(Request $request)
    {
        $game = Game::find($request->id);
        if ($game)
            $game->delete();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Game Delete Successfully ");
    }




}
