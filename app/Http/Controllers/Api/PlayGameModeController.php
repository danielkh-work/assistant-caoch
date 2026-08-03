<?php

namespace App\Http\Controllers\Api;

use App\Events\MatchLogCreated;
use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\BenchPlayer;
use App\Models\ConfiguredPlayingTeamPlayer;
use App\Models\Game;
use App\Models\League;
use App\Models\PlayGameLog;
use App\Models\PlayGameMode;
use App\Support\ActiveGameModeGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlayGameModeController extends Controller
{
    public function startGameGode(Request $request)
    {
        $request->validate([
            'league_id' => 'required|integer',
            'my_team_id' => 'required|integer',
            'oponent_team_id' => 'required|integer',
            'is_practice' => 'sometimes|boolean',
            'game_id' => 'sometimes|nullable|integer',
        ]);

        $user = auth()->user();
        $headCoachId = ActiveGameModeGuard::resolveHeadCoachId($user);
        $isPractice = filter_var($request->is_practice, FILTER_VALIDATE_BOOLEAN);
        $leagueId = $request->league_id ? (int) $request->league_id : null;

        $scheduledGame = $this->resolveScheduledGameForStart($request, $isPractice);
        if (! $isPractice && $scheduledGame && strtolower(trim((string) $scheduledGame->status)) === 'ended') {
            return new BaseResponse(
                STATUS_CODE_UNPROCESSABLE,
                STATUS_CODE_UNPROCESSABLE,
                "Game can't be started because it is already ended. (game_id: {$scheduledGame->id})"
            );
        }

        try {
            ActiveGameModeGuard::assertCanStart($headCoachId, $isPractice, $leagueId);
        } catch (ValidationException $e) {
            return new BaseResponse(
                STATUS_CODE_UNPROCESSABLE,
                STATUS_CODE_UNPROCESSABLE,
                collect($e->errors())->flatten()->first() ?? 'Cannot start game mode.',
            );
        }

        // Check if the league has an active device configured
        $league = League::whereKey($request->league_id)->first();
        if (!$league) {
            return new BaseResponse(
                STATUS_CODE_UNPROCESSABLE,
                STATUS_CODE_UNPROCESSABLE,
                'League not found.'
            );
        }

        // Verify league ownership
        if ((int) $league->user_id !== $headCoachId) {
            return new BaseResponse(
                STATUS_CODE_FORBIDDEN,
                STATUS_CODE_FORBIDDEN,
                'You do not have access to this league.'
            );
        }

        // Get the active device for the league
        $activeDevice = $league->devices()
            ->where('status', 'registered')
            ->first();

        if (!$activeDevice) {
            return new BaseResponse(
                STATUS_CODE_UNPROCESSABLE,
                STATUS_CODE_UNPROCESSABLE,
                'No device configured for this league. Please configure a device in League Settings.'
            );
        }

        if ($scheduledGame) {
            $gameType = $isPractice ? 2 : 1;
            // Always validate against the fixture's my team only — never require
            // opponent/visiting roster. Position UI can show field players from
            // either configured_playing_team_players or my-team bench rows.
            $myTeamId = (int) ($scheduledGame->my_team_id ?: $request->my_team_id);

            $hasConfiguredPlayers = ConfiguredPlayingTeamPlayer::query()
                ->where('match_id', $scheduledGame->id)
                ->where('team_id', $myTeamId)
                ->where(function ($query) use ($gameType) {
                    $query->where('game_type', $gameType)
                        ->orWhereNull('game_type');
                })
                ->exists();

            $hasBenchPlayers = BenchPlayer::query()
                ->where('game_id', $scheduledGame->id)
                ->where('team_id', $myTeamId)
                ->where('type', 'myteam')
                ->exists();

            if (! $hasConfiguredPlayers && ! $hasBenchPlayers) {
                return new BaseResponse(
                    STATUS_CODE_UNPROCESSABLE,
                    STATUS_CODE_UNPROCESSABLE,
                    'Cannot start match: your team has no players configured.'
                );
            }
        }

        // Only clean up orphaned opposite-mode scoreboard rows for THIS league.
        if ($isPractice) {
            DB::table('websocket_scoreboards')
                ->where('user_id', $headCoachId)
                ->where('league_id', $leagueId)
                ->delete();
        } else {
            DB::table('websocket_practice_scoreboards')
                ->where('user_id', $headCoachId)
                ->where('league_id', $leagueId)
                ->delete();
        }

        DB::beginTransaction();
        try {
            $game = new PlayGameMode();
            $game->sport_id = $user->sport_id;
            $game->league_id = $request->league_id;
            $game->my_team_id = $request->my_team_id;
            $game->oponent_team_id = $request->oponent_team_id;
            $game->game_mode = ActiveGameModeGuard::targetMode($isPractice);
            $game->user_id = $headCoachId;
            $game->device_id = $activeDevice->id;
            $game->quater = '';
            $game->downs = '';
            $game->status = ActiveGameModeGuard::STATUS_ACTIVE;
            $game->save();
            DB::commit();

            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, 'Game Start SuccessFully ', $game->load('device'));
        } catch (\Throwable $th) {
            DB::rollBack();

            return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
        }
    }

    private function resolveScheduledGameForStart(Request $request, bool $isPractice): ?Game
    {
        if ($request->filled('game_id')) {
            $game = Game::find((int) $request->game_id);
            if ($game) {
                return $game;
            }
        }

        // Without an explicit game_id, prefer the latest non-ended fixture for this pair.
        // Otherwise an older ended rematch of the same teams blocks starting a new one.
        $query = Game::query()
            ->where('league_id', $request->league_id)
            ->where('my_team_id', $request->my_team_id)
            ->where('oponent_team_id', $request->oponent_team_id)
            ->where('type', $isPractice ? 2 : 1);

        $nonEnded = (clone $query)
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereRaw('LOWER(TRIM(status)) != ?', ['ended']);
            })
            ->latest('id')
            ->first();

        if ($nonEnded) {
            return $nonEnded;
        }

        return $query->latest('id')->first();
    }


    public function addPointsObject(Request $request)
    {

        \Log::info(['data'=>$request->all()]);


        $value = $request->all();

        \Log::info(['value... log data'=>$value]);

        // If game_id is missing (AC's match.value not set yet), look up the HC's active session.
        if (!isset($value['game_id']) || !$value['game_id']) {
            $actorUser = auth()->user();
            $hcId = $actorUser->role === 'head_coach' ? $actorUser->id : $actorUser->head_coach_id;
            $isPractice = filter_var($value['is_practice'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $mode = $isPractice ? 'practice' : 'play';
            $activeGame = PlayGameMode::where('user_id', $hcId)
                ->where('game_mode', $mode)
                ->where('status', 2)
                ->latest('updated_at')
                ->first();
            if ($activeGame) {
                $value['game_id'] = $activeGame->id;
            }
        }

        if (empty($value) || !isset($value['game_id']) || !$value['game_id']) {
            return response()->json(['message' => 'Invalid data'], 400);
        }

        DB::beginTransaction();

        try {
        // Update game
        // $game = PlayGameMode::findOrFail($value['game_id']);
        // $game->save();

        // ✅ Create object & save
        $log = new PlayGameLog();
        $log->game_id = $value['game_id'];
        $log->sport_id = auth()->user()->sport_id;
        $log->league_id = $value['league_id'] ?? null;

        // ✅ new columns
        //$log->players = json_encode($value['players']) ?? null;

        if ($value['is_practice']) {
            $log->practice_players = !empty($value['players'])
                ? json_encode($value['players'])
                : null;
        } else {
            $log->players = !empty($value['players'])
                ? json_encode($value['players'])
                : null;
        }

        $log->confirmed = $value['is_confirmed'] ?? null;   // true / false / null

        $log->my_team_id = $value['my_team_id'] ?? null;
        $log->oponent_team_id = $value['oponent_team_id'] ?? null;
        $log->quater = $value['quater'] ?? null;
        $log->play_id = $value['play_id'] ?? null;
        $log->downs = $value['downs'] ?? null;
        $log->note = $value['note'] ?? null;
        $log->play_yardage_gain = isset($value['play_yardage_gain']) ? $value['play_yardage_gain'] : null;
        $log->weather_status = $value['weather_status'] ?? null;
        $log->current_position = $value['current_position'] ?? null;
        $log->target = $value['target'] ?? null;
        $log->my_points = $value['my_points'] ?? null;
        $log->oponent_points = $value['oponent_points'] ?? null;
        $log->time = $value['time'];
        $log->reasons = $value['reasons'] ?? '';
        $log->type_of_log = $value['type_of_log'];

        $actorUser = auth()->user();
        $log->actor_id   = $actorUser->id;
        $log->actor_role = $actorUser->role;
        $log->actor_name = $actorUser->name;

        $log->save(); // ✅ save() method

        DB::commit();

        // Broadcast the new log entry to all connected clients on this match channel
        try {
            $user = auth()->user();
            $coachGroupId = $user->role === 'head_coach' ? $user->id : $user->head_coach_id;

            if ($coachGroupId) {
                $log->load('myTeam', 'opponentTeam');

                if ($log->target == $log->my_team_id) {
                    $targetData = $log->myTeam;
                } elseif ($log->target == $log->oponent_team_id) {
                    $targetData = $log->opponentTeam;
                } else {
                    $targetData = null;
                }

                $logData = [
                    'id'               => $log->id,
                    'players'          => $value['is_practice'] ? $log->practice_players : $log->players,
                    'weather_status'   => $log->weather_status,
                    'play_yardage_gain'=> $log->play_yardage_gain,
                    'quater'           => $log->quater,
                    'time'             => $log->time,
                    'current_position' => $log->current_position,
                    'my_points'        => $log->my_points,
                    'target'           => $log->target,
                    'oponent_points'   => $log->oponent_points,
                    'downs'            => $log->downs,
                    'my_team'          => $log->myTeam,
                    'opponent_team'    => $log->opponentTeam,
                    'targetdata'       => $targetData,
                    'play'             => $log->target_team,
                    'type_of_log'      => $log->type_of_log,
                    'reasons'          => $log->reasons,
                    'confirmed'        => $log->confirmed,
                    'actor_id'         => $log->actor_id,
                    'actor_role'       => $log->actor_role,
                    'actor_name'       => $log->actor_name,
                    'players_out'      => $log->players_out,
                    'players_in'       => $log->players_in,
                ];

                // Fetch the device associated with this game for device-specific broadcasting
                $game = PlayGameMode::find($value['game_id']);
                $deviceId = $game ? $game->device_id : null;

                broadcast(new MatchLogCreated($logData, (int) $coachGroupId, (int) $value['game_id'], $deviceId));
            }
        } catch (\Exception $e) {
            \Log::error('MatchLogCreated broadcast failed: ' . $e->getMessage());
        }

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            "Update Added",
            $log
        );

        } catch (\Throwable $th) {
            DB::rollBack();

            return new BaseResponse(
                STATUS_CODE_BADREQUEST,
                STATUS_CODE_BADREQUEST,
                $th->getMessage()
            );
        }
    }

    public function addPoints(Request $request)
    {

     $data = $request->all();

   if (empty($data) || !is_array($data)) {
                return response()->json(true); // or return true;
    }
    $logs = [];


    DB::beginTransaction();

    try {

        foreach ($data as $value) {
            // Update the game record
            $game = PlayGameMode::find($value['game_id']);
            \Log::info(['game'=>$game]);
            \Log::info(['game_id'=>$value['game_id']]);
            $game->save();

            // Prepare the log data
            $logs[] = [
                'game_id' => $value['game_id'],
                'sport_id' => auth()->user()->sport_id,
                'league_id' => $value['league_id'],
                'player_id' => $value['player_id'],
                'my_team_id' => $value['my_team_id'],
                'oponent_team_id' => $value['oponent_team_id'],
                'quater' => $value['quater'],
                'downs' => $value['downs'],
                'weather_status' => $value['weather_status'],
                'current_position' => $value['current_position'],
                'target' => $value['target'],
                'my_points' => $value['my_points'],
                'oponent_points' => $value['oponent_points'],
                'time' => $value['time'],
                'reasons' => $value['reasons'] ?? '',
                'type_of_log' => $value['type_of_log'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        //  'players' => json_encode($value['players']), // array → json
        //     'confirmed' => $value['is_confirmed'],        // true / false

        // Insert all logs in a single query
        PlayGameLog::insert($logs);

        // Commit the transaction
        DB::commit();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Update Changes Added", $game);
    } catch (\Throwable $th) {
        // Rollback the transaction in case of an error
        DB::rollBack();

        return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
    }

    }
}
