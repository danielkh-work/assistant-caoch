<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Events\ScoreUpdated;
use App\Models\WebsocketScoreboard;
use App\Http\Responses\BaseResponse;

class BroadCastScoreController extends Controller
{
      public static $scores = [
        'left' => [
            'total' => 0
        ],
        'right' => [
            'total' => 0 
        ]
    ];

    public function scoreBoardBroadCast(Request $request)
    {
 
         
        
        $validated = $request->validate([
            'team' => 'required|in:left,right,both',
            'points' => 'required|integer',
            'action' => 'required|string'
        ]);
       
        $team = $validated['team'];
        $points = $validated['points'];
        $action = $validated['action'];
        if($team=='left'){
          self::$scores[$team]['total'] = $request->teamLeftScore+$points;
        }
        else if($team=='right'){
         self::$scores[$team]['total'] = $request->teamRightScore+$points;
        }else{

            self::$scores['left']['total'] = $request->teamLeftScore;
            self::$scores['right']['total'] = $request->teamRightScore;
        }
       
        WebsocketScoreboard::updateOrCreate(
            [
            'user_id' => auth()->id(),
        
            ],
            [
                'left_score' => self::$scores['left']['total'],
                'right_score' => self::$scores['right']['total'],
                'action' => $action,
                'game_id' => $request->game_id,
                'quarter' => $request->quarter,
                'time' => now()->toDateTimeString(),
                'is_start' => $request->isStartTime,
                'down' => $request->down,
                'team_position' => $request->teamPosition,
                'expected_yard_gain' => $request->expectedyardgain,
                'position_number' => $request->positionNumber,
                'pkg' => $request->pkg,
                'strategies' => $request->strategies,
                'possession' => $request->possession,
                
            ]
        );

   
        $payload = [
            'scores' => self::$scores,
            'team' => $team,
            'game_id' => $request->game_id,
             'user_id' => auth()->id(),
            'points' => $points,
            'action' => $action,
            'isStart'=>$request->isStartTime,
            'time'=>$request->time,
            'sys_time' => now()->toDateTimeString(), 
            'quarter' => $request->quarter,
            'down' => $request->down,
            'strategies' => $request->strategies,
            'teamPosition' => $request->teamPosition,
            'expectedyardgain' => $request->expectedyardgain,
            'positionNumber' => $request->positionNumber,
            'pkg' => $request->pkg,
            'possession' => $request->possession,
        ];

        $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;
        broadcast(new ScoreUpdated($payload, $coachGroupId,$request->game_id))->toOthers();

      
    }
   
    public function getWebSocketScoreBoard(){

        $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;
        $webSocketScorboard= WebsocketScoreboard::where('user_id',$coachGroupId)
        ->firstOrFail();
         if (!$webSocketScorboard) {
             return response()->noContent();
          }
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "scoreboardList",$webSocketScorboard);
        // return WebsocketScoreboard::where('game_id', $game_id)->firstOrFail();
    }

     public function delete(){
         $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;
           
        $deleted= WebsocketScoreboard::where('user_id',$coachGroupId)
        ->delete();
        if ($deleted) {
        
            broadcast(new ScoreUpdated((object)[], $coachGroupId, 1))->toOthers();
        }
        return response()->noContent();
        
    }
  
}
