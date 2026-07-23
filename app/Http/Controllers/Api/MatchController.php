<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\League;
use App\Models\PlayGameMode;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function index(League $league, Request $request) {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(100, (int) $request->input('per_page', 10)));

        $matches = $league->matches()
            ->where('user_id', auth()->id())
            ->with(['myTeam', 'opponentTeam'])
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $matches->getCollection()->transform(function ($match) {
            $myTeamName = $match->myTeam->team_name ?? 'My Team';
            $opponentTeamName = $match->opponentTeam->team_name ?? 'Opponent Team';
 
            if ($match->my_team_score > $match->oponent_team_score) {
                $match->my_team_status = 'WIN';
                $match->opponent_team_status = 'LOSS';
            } elseif ($match->my_team_score < $match->oponent_team_score) {
                $match->my_team_status = 'LOSS';
                $match->opponent_team_status = 'WIN';
            } else {
                $match->my_team_status = 'DRAW';
                $match->opponent_team_status = 'DRAW';
            }
 
            // Optional: Combine in one string if needed
            $match->summary = "{$myTeamName} ({$match->my_team_status}) vs {$opponentTeamName} ({$match->opponent_team_status})";
 
            return $match;
        });

        $pagination = [
            'total' => $matches->total(),
            'current_page' => $matches->currentPage(),
            'per_page' => $matches->perPage(),
            'last_page' => $matches->lastPage(),
        ];
 
        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            "Matches List  ",
            $matches->items(),
            null,
            null,
            $pagination
        );
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
