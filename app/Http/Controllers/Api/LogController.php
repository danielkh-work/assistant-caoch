<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\League;
use App\Models\PlayGameLog;

class LogController extends Controller
{
    private const TARGETDATA_FALLBACK_TYPES = [
        'down',
        'PositionNumber',
        'short',
        'medium',
        'long',
    ];

    public function index(League $league, $match)
    {
        $isPractice = request()->boolean('is_practice', false);

        $logs = PlayGameLog::where('league_id', $league->id)
            ->where('game_id', $match)
            ->with(['myTeam', 'opponentTeam'])
            ->get()
            ->map(function ($log) use ($isPractice) {
                $targetData = $this->resolveTargetData($log);

                return [
                    'id' => $log->id,
                    'players' => $isPractice
                        ? $log->practice_players
                        : $log->players,
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

    private function resolveTargetData(PlayGameLog $log)
    {
        if ((string) $log->target === (string) $log->my_team_id) {
            return $log->myTeam;
        }

        if ((string) $log->target === (string) $log->oponent_team_id) {
            return $log->opponentTeam;
        }

        if (in_array($log->type_of_log, self::TARGETDATA_FALLBACK_TYPES, true)) {
            return $log->myTeam ?? $log->opponentTeam;
        }

        return null;
    }
}
