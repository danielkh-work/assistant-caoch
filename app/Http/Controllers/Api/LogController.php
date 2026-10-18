<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\League;
use App\Models\PlayGameLog;
use App\Models\PlayGameMode;
use Illuminate\Http\Request;

class LogController extends Controller
{
    // Different parts of the live match write logs under two DIFFERENT id
    // conventions for the same real match: the background auto-logging
    // (matchEvents() - position/down/target/strategy/HashMark entries) tags
    // rows with the `games` table id straight from the route, but the actual
    // play-result logging (Run Play / Succeeded / Failed, via
    // savePlayGameLogObject) tags rows with the PlayGameMode SESSION id (a
    // fresh row created each time "Play Match" is clicked - restarting the
    // same game spawns a new session id, so one games.id can map to several
    // session ids over time). Nothing stores a link between the two id
    // spaces, so both must be queried together: the games.id itself (covers
    // the auto-logs, and covers a live-match caller that already passes a
    // session id that happens to have no auto-logs yet) PLUS every
    // PlayGameMode session for this game's league/teams (covers the
    // play-result logs). `$game->created_at` (when this fixture was
    // scheduled, never changes) anchors the lower bound so this doesn't
    // pull in a genuinely different rematch scheduled before this one.
    private function resolveLogGameIds(League $league, $match): array
    {
        $ids = [$match];

        $game = \App\Models\Game::where('id', $match)
            ->where('league_id', $league->id)
            ->first();
        if (! $game) {
            return $ids;
        }

        $since = $game->created_at ? \Carbon\Carbon::parse($game->created_at) : null;

        $sessionIds = PlayGameMode::where('league_id', $league->id)
            ->where('my_team_id', $game->my_team_id)
            ->where('oponent_team_id', $game->oponent_team_id)
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->pluck('id')
            ->all();

        return array_values(array_unique(array_merge($ids, $sessionIds)));
    }

 public function index(League $league, $match)
{

    $isPractice = request()->boolean('is_practice', false); // safely cast to boole

    $gameIds = $this->resolveLogGameIds($league, $match);

    $logs = PlayGameLog::where('league_id', $league->id)
        ->whereIn('game_id', $gameIds)
        ->with(['myTeam', 'opponentTeam'])
        ->latest()
        ->get()
        ->map(function ($log) use ($isPractice){
            
                if ($log->target == $log->my_team_id) {
                    $targetData = $log->myTeam; // full myTeam object
                } elseif ($log->target == $log->oponent_team_id) {
                    $targetData = $log->opponentTeam; // full opponentTeam object
                } else {
                    $targetData = null; // fallback if target is not set
                }
            return [
                'id'               => $log->id,
                'players'          => $isPractice ? $log->practice_players : $log->players,
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
                'play_id'          => $log->play_id,
                'play'             => $log->target_team,
                'created_at'       => $log->created_at,
                'type_of_log'      => $log->type_of_log,
                'confirmed'        => $log->confirmed,
                'actor_id'         => $log->actor_id,
                'actor_role'       => $log->actor_role,
                'actor_name'       => $log->actor_name,
                'players_out'      => $log->players_out,
                'players_in'       => $log->players_in,
            ];
        });

    return new BaseResponse(
        STATUS_CODE_OK,
        STATUS_CODE_OK,
        "Logs List",
        $logs
    );
}

    // Lets a coach fill in (or correct) who was on the field for a specific play
    // run after the fact - deliberately has no "match must be live" check, since
    // this needs to keep working after the game has ended.
    public function updatePlayers(League $league, $match, PlayGameLog $log, Request $request)
    {
        $gameIds = $this->resolveLogGameIds($league, $match);
        abort_unless(
            (int) $log->league_id === (int) $league->id && in_array((int) $log->game_id, array_map('intval', $gameIds), true),
            404
        );

        $data = $request->validate([
            'players'     => 'array',
            'is_practice' => 'boolean',
        ]);

        if ($request->boolean('is_practice')) {
            $log->practice_players = $data['players'] ?? [];
        } else {
            $log->players = $data['players'] ?? [];
        }
        $log->save();

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            "Players updated",
            $log->fresh()
        );
    }

}
