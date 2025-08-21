<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Events\ScoreUpdated;

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
 
         \Log::info(['team' => $request->all()]);
        
        $validated = $request->validate([
            'team' => 'required|in:left,right',
            'points' => 'required|integer',
            'action' => 'required|string'
        ]);
       
        $team = $validated['team'];
        $points = $validated['points'];
        $action = $validated['action'];
        if($team=='left'){
          self::$scores[$team]['total'] = $request->teamLeftScore+$points;
        }
        if($team=='right'){
         self::$scores[$team]['total'] = $request->teamRightScore+$points;
        }
       
       
        broadcast(new ScoreUpdated(self::$scores));
      


        // broadcast(new ScoreUpdated(self::$scores))->toOthers();

        // return response()->json(self::$scores);
    }
}
