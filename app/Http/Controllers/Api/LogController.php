<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\League;
use App\Models\PlayGameLog;
use App\Models\PlayGameMode;

class LogController extends Controller
{
    public function index(League $league, $match) {
        $logs = PlayGameLog::where('league_id', $league->id)->where('game_id', $match)->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Logs List  ", $logs);
    }
}
