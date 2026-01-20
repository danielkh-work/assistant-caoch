<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Events\PracticeScoreUpdated;
use App\Events\ScoreUpdated;
use App\Events\TeamScoreUpdated;
use App\Events\PlaySuggested;

use App\Models\WebsocketScoreboard;
use App\Models\WebsocketPracticeScoreboard;
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

     public static $qb = [
        'left' => [
            'total' => 0
        ],
        'right' => [
            'total' => 0 
        ]
    ];

     public function practiceScoreBoardBroadCast(Request $request)
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
       
        $record = WebsocketPracticeScoreboard::firstOrNew(
            [
             'user_id' => auth()->id(),
             'game_id' => $request->game_id,
             
            ] // lookup condition
             
        );


       
        if (!$record->exists) {
        $record->time = \Carbon\Carbon::now('America/New_York')->toDateTimeString();
        }

        // Always update these fields
        $record->left_score = self::$scores['left']['total'];
        $record->right_score = self::$scores['right']['total'];
        $record->action = $action;
        $record->game_id = $request->game_id;
      
        $record->quarter = $request->quarter;
        $record->is_start = $request->isStartTime;
        $record->down = $request->down;
        $record->team_position = $request->teamPosition;
        $record->expected_yard_gain = $request->expectedyardgain;
        $record->position_number = $request->positionNumber;
        $record->pkg = $request->pkg;
        $record->strategies = $request->strategies;
        $record->possession = $request->possession;

        // Save (creates or updates)
        $record->save();

   
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
        \Log::info('Before broadcast');
        broadcast(new PracticeScoreUpdated($payload, $coachGroupId, $request->game_id))->toOthers();
        \Log::info('After broadcast');

    }
    

    public function scoreBoardBroadCastQB(Request $request)
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
            $operation = strtolower(trim($request->operation));
            $adjustedPoints = ($operation == 'subtract')
            ? $request->teamLeftScore - $points
            : $request->teamLeftScore + $points;

          self::$qb[$team]['total'] =  $adjustedPoints;
        }
        else if($team=='right'){
            $operation = strtolower(trim($request->operation));
            $adjustedPoints = ($operation == 'subtract')
            ? $request->teamRightScore - $points
            : $request->teamRightScore + $points;
           // $request->teamRightScore+$points;
             self::$qb[$team]['total'] = $adjustedPoints;
        }else{

            self::$qb['left']['total'] = $request->teamLeftScore;
            self::$qb['right']['total'] = $request->teamRightScore;
        }
       
        $payload = [
            'scores' => self::$qb,
            'left'=>$request->myteam,      
            'right'=>$request->oponentTeam,      
            'points' => $points,
            'quarter_length'=>$request->quarter_length/4,
            'isStart'=>$request->isStartTime,
          
            // 'sys_time' => now()->toDateTimeString(), 
            // 'quarter' => $request->quarter,
            // 'down' => $request->down,
            // 'strategies' => $request->strategies,
            // 'teamPosition' => $request->teamPosition,
            // 'expectedyardgain' => $request->expectedyardgain,
            // 'positionNumber' => $request->positionNumber,
            // 'pkg' => $request->pkg,
            // 'possession' => $request->possession,
        ];
           
      
        $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;
         
         

       
         broadcast(new TeamScoreUpdated($payload, $coachGroupId))->toOthers();

      
    }

    public function scoreBoardBroadCastPlay(Request $request)
    {
        
         
        $validated = $request->validate([
            'title' => 'required|string',
            'image' => 'required|string',
            'type'  => 'required|in:offensive,defensive',

            // Optional fields
            'read1' => 'nullable|string',
            'read2' => 'nullable|string',
        ]);

        // Access validated data
        $payload['title'] = $validated['title'];
        $payload['image'] = $validated['image'];
        $payload['type']  = $validated['type'];
        $payload['read1'] = $validated['read1'] ?? null;
        $payload['read2'] = $validated['read2'] ?? null;

        \Log::info(['playe suggested broad cast'=>  $payload]);
      
        $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;
         
         

       
         broadcast(new PlaySuggested($payload, $coachGroupId))->toOthers();

      
    }

   
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
        \Log::info(['operation'=>$request->operation]);
        if($team=='left'){

         

            $operation = strtolower(trim($request->operation));
            $adjustedPoints = ($operation == 'subtract')
            ? $request->teamLeftScore - $points
            : $request->teamLeftScore + $points;

  


          self::$scores[$team]['total'] =  $adjustedPoints;
        }
        else if($team=='right'){
            $operation = strtolower(trim($request->operation));
            $adjustedPoints = ($operation == 'subtract')
            ? $request->teamRightScore - $points
            : $request->teamRightScore + $points;
           // $request->teamRightScore+$points;
             self::$scores[$team]['total'] = $adjustedPoints;
        }else{

            self::$scores['left']['total'] = $request->teamLeftScore;
            self::$scores['right']['total'] = $request->teamRightScore;
        }
       
        $record = WebsocketScoreboard::firstOrNew(
            [
             'user_id' =>  auth()->user()->role === 'head_coach'
        ? auth()->id()
        : auth()->user()->head_coach_id,
           
             'game_id' => $request->game_id,
            ] // lookup condition
            
        );

       
        // if (!$record->exists ) {
        // $record->time = \Carbon\Carbon::now('America/New_York')->toDateTimeString();
        // }
       
if (
    !$record->exists || 
    ($record->exists && $record->quarter != $request->quarter)
) {
    $record->time = \Carbon\Carbon::now('America/New_York')->toDateTimeString();
}
        // Always update these fields
        $record->left_score = self::$scores['left']['total'];
        $record->right_score = self::$scores['right']['total'];
        $record->action = $action;
        $record->sync_time = $request->sync_time;
        $record->game_id = $request->game_id;
        $record->quarter = $request->quarter;
        $record->is_start = $request->isStartTime;
        $record->down = $request->down;
        $record->team_position = $request->teamPosition;
        $record->expected_yard_gain = $request->expectedyardgain;
        $record->position_number = $request->positionNumber;
        $record->pkg = $request->pkg;
        $record->strategies = $request->strategies;
        $record->possession = $request->possession;

        // Save (creates or updates)
        $record->save();

   
        $payload = [
            'scores' => self::$scores,
            'team' => $team,
            'game_id' => $request->game_id,
          'user_id' => auth()->user()->role === 'head_coach'
        ? auth()->id()
        : auth()->user()->head_coach_id,

            'points' => $points,
            'action' => $action,
            'sync_time' => $request->sync_time,
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

    public function getPracticeWebSocketScoreBoard(){

        $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;
        $webSocketScorboard= WebsocketPracticeScoreboard::where('user_id',$coachGroupId)
        ->firstOrFail();
         if (!$webSocketScorboard) {
             return response()->noContent();
          }
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "scoreboardList",$webSocketScorboard);
        // return WebsocketScoreboard::where('game_id', $game_id)->firstOrFail();
    }
    

     public function delete($gameId){
         $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;
           
        $deleted= WebsocketScoreboard::where('user_id',$coachGroupId)
        ->delete();
        if ($deleted) {
          broadcast(new ScoreUpdated((object)[], $coachGroupId,$gameId))->toOthers();
          
        }
        return response()->noContent();
        
    }

    public function deletePractice($gameId){
        \Log::info(['gameid'=>$gameId]);
        $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;
           
        $deleted= WebsocketPracticeScoreboard::where('user_id',$coachGroupId)
        ->delete();
        if ($deleted) {
          
             broadcast(new PracticeScoreUpdated((object)[], $coachGroupId,$gameId))->toOthers();
           
             return response()->noContent();
        
         }
  }
}
