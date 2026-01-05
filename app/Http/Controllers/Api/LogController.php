<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\League;
use App\Models\PlayGameLog;
use App\Models\PlayGameMode;

class LogController extends Controller
{
 public function index(League $league, $match)
{
    $logs = PlayGameLog::where('league_id', $league->id)
        ->where('game_id', $match)
        ->with(['myTeam', 'opponentTeam'])
        ->get()
        ->map(function ($log) {
            return [
                'id' => $log->id,
                'players' => $log->players,
                'quater' => $log->quater,
                'time' => $log->time,
                'my_points' => $log->my_points,
                'oponent_points' => $log->oponent_points,
                'downs' => $log->downs,
                'my_team' => $log->myTeam,
                'opponent_team' => $log->opponentTeam,
                'target' => $log->target,

                // ✅ already normalized by accessor
                'play' => $log->target_team,

                'type_of_log' => $log->type_of_log,
                'confirmed' => $log->confirmed,
            ];
        });

    return new BaseResponse(
        STATUS_CODE_OK,
        STATUS_CODE_OK,
        "Logs List",
        $logs
    );
}


}
