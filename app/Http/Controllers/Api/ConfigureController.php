<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Events\MatchLogCreated;
use App\Models\ConfiguredPlayingTeamPlayer;
use App\Models\ConfigureFormation;
use App\Models\ConfigurePlay;
use App\Models\ConfigureDefensivePlay;
use App\Models\Game;
use App\Models\PersionalGrouping;
use App\Models\PlayGameLog;
use App\Models\PlayGameMode;
use App\Models\Player;
use App\Models\PracticeTeamPlayer;
use App\Models\TeamPlayer;
use App\Models\WebsocketPracticeScoreboard;
use App\Models\WebsocketScoreboard;
use App\Support\ActiveGameModeGuard;
use App\Support\BroadcastLeagueDeviceResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfigureController extends Controller
{
    public function store(Request $request)
    {
       
        DB::beginTransaction();
        try {
            $teamId = (int) $request->team_id;
            $matchId = (int) $request->match_id;
            $gameType = (int) $request->game_type;
            $teamType = 1;

            $playerIds = $request->input('player_id', []);
            if (! is_array($playerIds)) {
                $playerIds = $playerIds !== null && $playerIds !== '' ? [$playerIds] : [];
            }

            $types = $request->input('type', []);
            if (! is_array($types)) {
                $types = $types !== null && $types !== '' ? [$types] : [];
            }

            $previousRosterIds = PersionalGrouping::configureRosterPlayerIdsForTeamSide(
                $teamId,
                $matchId,
                $gameType,
                $teamType
            );

            // Replace the full roster for this match: if we only deleted rows matching
            // the types present in the request, omitted squads (e.g. all offensive
            // removed while saving defensive only) would never be cleared.
            ConfiguredPlayingTeamPlayer::where('team_id', $teamId)
                ->where('match_id', $matchId)
                ->where('game_type', $gameType)
                ->where('team_type', $teamType)
                ->delete();
    
         
            foreach ($playerIds as $index => $playerId) {
     

                    ConfiguredPlayingTeamPlayer::create([
                        'team_id' => $teamId,
                        'match_id' => $matchId,
                        'type' => $types[$index] ?? 'offensive',
                        'team_type' => $teamType,
                        'game_type' => $gameType,
                        'player_id' => $gameType == 1 ? $playerId : null,
                        'practice_player_id' => $gameType != 1 ? $playerId : null,
                    ],[] );
            }

            $newRosterIds = array_values(array_unique(array_map('intval', $playerIds)));
            $removedRosterIds = array_values(array_diff($previousRosterIds, $newRosterIds));

            $liveSession = $this->resolveLiveSessionForScheduledGame($matchId, $gameType);
            $removalReason = trim((string) $request->input('removal_reason', ''));

            if ($liveSession && $removedRosterIds !== [] && $removalReason === '') {
                DB::rollBack();

                return new BaseResponse(
                    STATUS_CODE_UNPROCESSABLE,
                    STATUS_CODE_UNPROCESSABLE,
                    'A reason is required to remove players while the match is in progress.'
                );
            }
          
           DB::commit();
           PersionalGrouping::syncAfterConfigureRosterSave(
               $teamId,
               $matchId,
               $gameType,
               $removedRosterIds
           );

           $removalLogged = false;
           if ($liveSession && $removedRosterIds !== [] && $removalReason !== '') {
               $this->logLiveRosterRemoval(
                   $liveSession,
                   $matchId,
                   $gameType,
                   $removedRosterIds,
                   $removalReason
               );
               $removalLogged = true;
           }

           return new BaseResponse(
               STATUS_CODE_OK,
               STATUS_CODE_OK,
               'configure Player successFully',
               [
                   'removed_player_ids' => $removedRosterIds,
                   'removal_logged' => $removalLogged,
               ]
           );
        } catch (\Throwable $th) {
          DB::rollBack();
          return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
        }
    }
    public function storevisiting(Request $request)
    {
        DB::beginTransaction();
        try {
            $teamId = (int) $request->team_id;
            $matchId = (int) $request->match_id;
            $gameType = (int) $request->game_type;
            $teamType = 2;

            $playerIds = $request->input('player_id', []);
            if (! is_array($playerIds)) {
                $playerIds = $playerIds !== null && $playerIds !== '' ? [$playerIds] : [];
            }

            $types = $request->input('type', []);
            if (! is_array($types)) {
                $types = $types !== null && $types !== '' ? [$types] : [];
            }

            $previousRosterIds = PersionalGrouping::configureRosterPlayerIdsForTeamSide(
                $teamId,
                $matchId,
                $gameType,
                $teamType
            );

            ConfiguredPlayingTeamPlayer::where('team_id', $teamId)
                ->where('match_id', $matchId)
                ->where('game_type', $gameType)
                ->where('team_type', $teamType)
                ->delete();
            foreach ($playerIds as $index => $playerId) {

                    ConfiguredPlayingTeamPlayer::create([
                        'team_id' => $teamId,
                        'match_id' => $matchId,
                        'type' => $types[$index] ?? 'offensive',
                        'team_type' => $teamType,
                        'game_type' => $gameType,
                        'player_id' => $gameType == 1 ? $playerId : null,
                        'practice_player_id' => $gameType != 1 ? $playerId : null,
                    ],[]);
        
            }

            $newRosterIds = array_values(array_unique(array_map('intval', $playerIds)));
            $removedRosterIds = array_values(array_diff($previousRosterIds, $newRosterIds));

           DB::commit();
           PersionalGrouping::syncAfterConfigureRosterSave(
               $teamId,
               $matchId,
               $gameType,
               $removedRosterIds
           );
           return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure Player successFully");
        } catch (\Throwable $th) {
          DB::rollBack();
          return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
        }
    }

    /**
     * @return object{session_id: int, scoreboard: object}|null
     */
    private function resolveLiveSessionForScheduledGame(int $scheduledGameId, int $gameType): ?object
    {
        $isPractice = $gameType === 2;
        $row = $isPractice
            ? WebsocketPracticeScoreboard::query()->where('game_id', $scheduledGameId)->latest('updated_at')->first()
            : WebsocketScoreboard::query()->where('game_id', $scheduledGameId)->latest('updated_at')->first();

        if (! $row || ! ($row->is_start ?? false) || ($row->action ?? null) === 'EndMatch') {
            return null;
        }

        $user = auth()->user();
        if (! $user) {
            return null;
        }

        $headCoachId = ActiveGameModeGuard::resolveHeadCoachId($user);
        $mode = $isPractice ? 'practice' : 'play';
        $table = $isPractice ? 'websocket_practice_scoreboards' : 'websocket_scoreboards';

        if (! ActiveGameModeGuard::scoreboardIndicatesLive($row, $headCoachId, $mode)) {
            return null;
        }

        $sessionId = ! empty($row->session_id)
            ? (int) $row->session_id
            : PlayGameMode::query()
                ->where('user_id', $headCoachId)
                ->where('game_mode', $mode)
                ->where('status', ActiveGameModeGuard::STATUS_ACTIVE)
                ->when($row->league_id, fn ($q) => $q->where('league_id', (int) $row->league_id))
                ->latest('updated_at')
                ->value('id');

        if (! $sessionId) {
            return null;
        }

        // Touch keeps the live row from looking abandoned during roster edits.
        ActiveGameModeGuard::touchLiveScoreboardRow($row, $table);

        return (object) [
            'session_id' => (int) $sessionId,
            'scoreboard' => $row,
        ];
    }

    /**
     * @param  list<int>  $removedRosterIds
     */
    private function logLiveRosterRemoval(
        object $liveSession,
        int $scheduledGameId,
        int $gameType,
        array $removedRosterIds,
        string $removalReason
    ): void {
        try {
            $user = auth()->user();
            if (! $user) {
                return;
            }

            $headCoachId = ActiveGameModeGuard::resolveHeadCoachId($user);
            $sessionId = (int) $liveSession->session_id;
            $scheduledGame = Game::find($scheduledGameId);
            $session = PlayGameMode::find($sessionId);

            $outPlayers = $this->resolveRemovedPlayerPayloads($removedRosterIds, $gameType);

            $log = new PlayGameLog();
            $log->game_id = $sessionId;
            $log->sport_id = $user->sport_id;
            $log->league_id = $scheduledGame?->league_id ?? $session?->league_id;
            $log->my_team_id = $scheduledGame?->my_team_id ?? $session?->my_team_id;
            $log->oponent_team_id = $scheduledGame?->oponent_team_id ?? $session?->oponent_team_id;
            $log->type_of_log = 'player_removed';
            $log->reasons = $removalReason;
            $log->players_out = $outPlayers;
            $log->players_in = [];
            $log->time = $liveSession->scoreboard->sync_time
                ?? $liveSession->scoreboard->timer_remaining
                ?? null;
            $log->quater = $liveSession->scoreboard->quarter ?? null;
            $log->downs = $liveSession->scoreboard->down ?? null;
            $log->actor_id = $user->id;
            $log->actor_role = $user->role;
            $log->actor_name = $user->name;
            $log->save();

            $log->load('myTeam', 'opponentTeam');
            $logData = [
                'id' => $log->id,
                'type_of_log' => $log->type_of_log,
                'reasons' => $log->reasons,
                'actor_id' => $log->actor_id,
                'actor_role' => $log->actor_role,
                'actor_name' => $log->actor_name,
                'players_out' => $outPlayers,
                'players_in' => [],
                'my_team' => $log->myTeam,
                'opponent_team' => $log->opponentTeam,
                'quater' => $log->quater,
                'time' => $log->time,
                'downs' => $log->downs,
            ];

            $deviceId = BroadcastLeagueDeviceResolver::deviceIdForGame($sessionId);
            broadcast(new MatchLogCreated($logData, (int) $headCoachId, $sessionId, $deviceId));
        } catch (\Throwable $e) {
            Log::error('Live roster removal log failed: '.$e->getMessage());
        }
    }

    /**
     * @param  list<int>  $removedRosterIds
     * @return list<array{id: int, name: string}>
     */
    private function resolveRemovedPlayerPayloads(array $removedRosterIds, int $gameType): array
    {
        if ($removedRosterIds === []) {
            return [];
        }

        if ($gameType === 2) {
            return PracticeTeamPlayer::query()
                ->whereIn('id', $removedRosterIds)
                ->get()
                ->map(fn ($p) => [
                    'id' => (int) $p->id,
                    'name' => (string) ($p->name ?? $p->player_name ?? 'Unknown'),
                ])
                ->values()
                ->all();
        }

        $teamPlayers = TeamPlayer::query()
            ->whereIn('id', $removedRosterIds)
            ->get();

        $playerIds = $teamPlayers->pluck('player_id')->filter()->unique()->values();
        $namesByPlayerId = Player::query()
            ->whereIn('id', $playerIds)
            ->pluck('name', 'id');

        return $teamPlayers
            ->map(fn (TeamPlayer $tp) => [
                'id' => (int) $tp->id,
                'name' => (string) ($namesByPlayerId[$tp->player_id] ?? $tp->player_name ?? 'Unknown'),
            ])
            ->values()
            ->all();
    }

    public function view(Request $request)
    {
     

        // Base query (runs in all cases)
        $query = ConfiguredPlayingTeamPlayer::with(
            'player.teamPlayerPosition',
            'player.player.playerPosition',
            'practice_player.positions',
        )
        ->where('team_id', $request->team_id)
        ->where('match_id', $request->game_id);

        

        $configured = $query->get();

       
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure Player List",$configured);
    }

    public function configureFormation(Request $request)
    {
        DB::beginTransaction();
        try {
            ConfigureFormation::where(['user_id'=> auth()->user()->id,'league_id'=>$request->league_id])->delete();
            $configureFormation =  new ConfigureFormation();
            $configureFormation->user_id = auth()->user()->id;
            $configureFormation->formation_id = $request->formation_id;
            $configureFormation->league_id =  $request->league_id;
            $configureFormation->save();

            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure formation successFully");
        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
        }
    }
    public function configureFormationView(Request $request)
    {
        $configure =  ConfigureFormation::with('league','team')->where('league_id',$request->league_id)->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure formation List",$configure);
  
    }
    /**
     * Every play id the current user can see for this league, respecting the same
     * league/role visibility and search filter as PlayController::index() - used by
     * configurePlay()'s select_all path so "select all" never has to round-trip every
     * play id through the frontend first.
     *
     * @return array<int, int>
     */
    private function matchingPlayIdsForSelectAll(Request $request): array
    {
        $userRoleIds = auth()->user()->roles->pluck('id');
        $leagueScope = ['1', $request->league_id];

        $query = \App\Models\Play::query()
            ->where(function ($sub) use ($leagueScope, $userRoleIds) {
                $sub->orWhereIn('league_id', $leagueScope)
                    ->orWhereHas('roles', function ($q) use ($userRoleIds) {
                        $q->whereIn('roleables.role_id', $userRoleIds);
                    });
            });

        $searchTerm = trim((string) $request->input('search', ''));
        if ($searchTerm !== '') {
            $needle = '%' . addcslashes($searchTerm, '%_\\') . '%';
            $query->where('play_name', 'like', $needle);
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function configurePlay(Request $request)
    {
        DB::beginTransaction();
        try {
            $selectAll = filter_var($request->input('select_all', false), FILTER_VALIDATE_BOOLEAN);

            if ($selectAll) {
                $playIds = $this->matchingPlayIdsForSelectAll($request);

                $excludeIds = $request->input('exclude_play_id', []);
                if (! is_array($excludeIds)) {
                    $excludeIds = $excludeIds !== null && $excludeIds !== '' ? [$excludeIds] : [];
                }
                if (! empty($excludeIds)) {
                    $excludeIds = array_map('intval', $excludeIds);
                    $playIds = array_values(array_diff($playIds, $excludeIds));
                }
            } else {
                $playIds = $request->input('play_id', []);
                if (!is_array($playIds)) {
                    $playIds = $playIds !== null && $playIds !== '' ? [$playIds] : [];
                }
                $playIds = array_values(array_unique(array_filter(array_map('intval', $playIds), fn ($id) => $id > 0)));
            }

            ConfigurePlay::where([
                'user_id' => auth()->user()->id,
                'league_id' => $request->league_id,
                'match_id' => $request->matchId,
            ])->delete();
            foreach ($playIds as $value) {

                $configureFormation = new ConfigurePlay();
                $configureFormation->user_id = auth()->user()->id;
                $configureFormation->play_id = $value;
                $configureFormation->match_id = $request->matchId;
                $configureFormation->league_id = $request->league_id;
                $configureFormation->save();
            }

            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure Play successFully");
        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
        }
    }

     public function configureDefensivePlay(Request $request)
    {
        DB::beginTransaction();
        try {
            ConfigureDefensivePlay::where(['user_id'=> auth()->user()->id,'league_id'=>$request->league_id,'game_id'=>$request->matchId])->delete();
            foreach($request->play_id as $value)
            {

                $configureFormation =  new ConfigureDefensivePlay();
                $configureFormation->user_id = auth()->user()->id;
                $configureFormation->play_id = $value;
                $configureFormation->game_id = $request->matchId;
                $configureFormation->league_id =  $request->league_id;
                $configureFormation->save();
            }

            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure Play successFully");
        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
        }
    }
    public function configurePlayView(Request $request)
    {
        $configure = ConfigurePlay::with('league', 'play')
            ->where('user_id', auth()->id())
            ->where('league_id', $request->league_id)
            ->where('match_id', $request->matchId)
            ->get();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure Play List", $configure);
  
    }
     public function configurePlayDefensiveView(Request $request)
    {
        $configure = ConfigureDefensivePlay::with('league', 'play')
            ->where('user_id', auth()->id())
            ->where('league_id', $request->league_id)
            ->where('game_id', $request->matchId)
            ->get();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure Play List", $configure);
  
    }
    
}
