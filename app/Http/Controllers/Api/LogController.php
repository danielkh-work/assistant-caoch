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
            
                if ($log->target == $log->my_team_id) {
                    $targetData = $log->myTeam; // full myTeam object
                } elseif ($log->target == $log->oponent_team_id) {
                    $targetData = $log->opponentTeam; // full opponentTeam object
                } else {
                    $targetData = null; // fallback if target is not set
                }
            return [

                'id' => $log->id,
                'players' => $log->players,
                'weather_status' => $log->weather_status,
                'play_yardage_gain' => $log->play_yardage_gain,
                'quater' => $log->quater,
                'time' => $log->time,
                'current_position' => $log->current_position,
                'my_points' => $log->my_points,
                'target' => $log->target, 
                'oponent_points' => $log->oponent_points,
                'downs' => $log->downs,
                'my_team' => $log->myTeam,
                'opponent_team' => $log->opponentTeam,
                'targetdata' => $targetData,
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
