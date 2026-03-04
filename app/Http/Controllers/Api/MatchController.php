<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\League;
use App\Models\PlayGameMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MatchController extends Controller
{
    public function index(League $league) {
        $matches = $league->matches()->where('user_id', auth()->id())->with(['myTeam', 'opponentTeam'])->get();
 
        $matches = $matches->map(function ($match) {
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
    public function liveMatches(Request $request)
    {
        $user = auth()->user();
        $coachId = in_array($user->role, ['assistant_coach', 'performance_coach'])
            ? $user->head_coach_id
            : $user->id;
        $matches = DB::table('websocket_scoreboards as ws')
        ->join('games as g', 'g.id', '=', 'ws.game_id')
        ->join('leagues as l', 'l.id', '=', 'g.league_id')
        ->where('ws.user_id', $coachId)
        ->where('ws.is_start', 1)
        ->select(
            'ws.game_id as id',
        
            'l.title as league_name',
            DB::raw('MIN(ws.time) as created_at')
        )
    ->groupBy('ws.game_id', 'l.title')
    ->get()
    ->map(function ($match) {
        return [
            'id' => $match->id,                    // ✅ use alias
            'name' => $match->league_name,         // ✅ use league title
            'type' => 'Match',
            'start_time' => Carbon::parse($match->created_at)->format('h:i A'),
            
        ];
    });
        $practices = DB::table('websocket_practice_scoreboards as ws')
    ->join('games as g', 'g.id', '=', 'ws.game_id')
    ->join('leagues as l', 'l.id', '=', 'g.league_id')
    ->where('ws.user_id', $coachId)
    ->where('ws.is_start', 1)
    ->select(
        'ws.game_id as id',
        'l.title as league_name',
        DB::raw('MIN(ws.created_at) as created_at')
    )
    ->groupBy('ws.game_id', 'l.title') // ✅ REQUIRED
    ->get()
    ->map(function ($practice) {
        return [
            'id' => $practice->id,
            'name' => $practice->league_name, // cleaner
            'type' => 'Practice',
            'start_time' => Carbon::parse($practice->created_at)->format('h:i A'),
        ];
    });
    
    return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Matches List", $matches->merge($practices));    
    }
  
}
