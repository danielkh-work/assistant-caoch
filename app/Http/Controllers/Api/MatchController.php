<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\League;
use App\Models\PlayGameMode;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function index(League $league) {
        $matches = $league->matches()->with(['myTeam', 'opponentTeam'])->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Matches List  ", $matches);
    }

    public function update(League $league, $match, Request $request) {
        $match = PlayGameMode::where('league_id', $league->id)->where('id', $match)->first();

        if (!$match) {
            return new BaseResponse(404, false, "Match not found");
        }
        
        $match->my_team_score = $request->my_team_score;
        $match->oponent_team_score = $request->oponent_team_score;
        $match->save();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Match update successfully", $match);
    }
}
