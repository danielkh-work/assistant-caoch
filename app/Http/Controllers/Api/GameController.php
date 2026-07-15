<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\BenchPlayer;
use App\Models\LeagueTeam;
use App\Models\PersionalGrouping;
use Illuminate\Http\Request;
use App\Http\Responses\BaseResponse;
use App\Models\Penality;
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
            'date' => 'required',
            'location' => 'nullable',
            'neutral_location'  => 'nullable',
            'location_type'   => 'required|string|in:home,visiting,neutral',
        ]);
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

    public function duplicate(Request $request, $id)
    {
        $request->validate([
            'date' => 'sometimes|nullable|date',
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

        if ($newOpponentTeamId !== (int) $game->oponent_team_id) {
            $opponentQuery = LeagueTeam::query()
                ->whereKey($newOpponentTeamId)
                ->where('league_id', $game->league_id);

            if (Schema::hasColumn('league_teams', 'is_practice')) {
                $opponentQuery->where(function ($q) {
                    $q->where('is_practice', 0)
                        ->orWhereNull('is_practice');
                });
            }

            if (! $opponentQuery->exists()) {
                return new BaseResponse(
                    STATUS_CODE_UNPROCESSABLE,
                    STATUS_CODE_UNPROCESSABLE,
                    'Selected opponent team is invalid for this league.'
                );
            }
        }

        $opponentChanged = $newOpponentTeamId !== (int) $game->oponent_team_id;

        DB::beginTransaction();
        try {
            $duplicate = $game->replicate();
            $duplicate->oponent_team_id = $newOpponentTeamId;

            if ($request->filled('date') && Schema::hasColumn('games', 'date')) {
                $duplicate->date = $request->input('date');
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
          \Log::info(['data'=>'checkit working ornot']);
            $gamesQuery = Game::with([
                'myTeam',
                'opponentTeam',
                'configuredPlays',
                'configureMyTeams',
                'configureVisitingTeams'
            ])

            ->where('league_id', $leagueId);


            $gameType = request()->query('type');
            if ($gameType !== null) {
                $gamesQuery->where('type', $gameType);
            }

            if ((int) ($gameType ?? 1) !== 2) {
                $gamesQuery->where(function ($query) {
                    $query->where('type', 2)
                        ->orWhereNull('status')
                        ->orWhere('status', '!=', 'ended');
                });
            }

            $games = $gamesQuery->get();
                    return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "games list", $games);
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
