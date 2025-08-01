<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Play;
use App\Models\DefensivePlay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class SuggestionController extends Controller
{
    // comment By noor
    // public function getSuggestedPlays($league, Request $request)
    // {
    //     $validated = $request->validate([
    //         'zone' => 'required|integer',
    //         'down' => 'required|integer|in:1,2,3,4',
    //         'possession' => 'required|string|in:offensive,defensive',
    //     ]);

    //     // Step 1: Match all three
    //     $plays = Play::where('zone_selection', $validated['zone'])
    //         ->where('preferred_down', $validated['down'])
    //         ->where('possession', $validated['possession'])
    //         ->take(3)
    //         ->get();

    //     if ($plays->count() >= 3) {
    //         return $plays;
    //     }

    //     // Step 2: Match any two
    //     $bestMatch = collect(); // Will hold the best fallback result

    //     $combinations = [
    //         ['zone_selection', 'preferred_down'],
    //         ['zone_selection', 'possession'],
    //         ['preferred_down', 'possession'],
    //     ];

    //     foreach ($combinations as $combo) {
    //         $query = Play::query();

    //         foreach ($combo as $field) {
    //             if ($field === 'zone_selection') {
    //                 $query->where($field, $validated['zone']);
    //             } elseif ($field === 'preferred_down') {
    //                 $query->where($field, $validated['down']);
    //             } else {
    //                 $query->where($field, $validated['possession']);
    //             }
    //         }

    //         $result = $query->take(3)->get();
    //         if ($result->count() >= 3) {
    //             return $result;
    //         }

    //         // Store the best result so far
    //         if ($result->count() > $bestMatch->count()) {
    //             $bestMatch = $result;
    //         }
    //     }

    //     // Step 3: Match any one
    //     $fields = [
    //         'zone_selection' => $validated['zone'],
    //         'preferred_down' => $validated['down'],
    //         'possession' => $validated['possession'],
    //     ];

    //     foreach ($fields as $field => $value) {
    //         $result = Play::where($field, $value)->take(3)->get();
    //         if ($result->count() >= 3) {
    //             return $result;
    //         }

    //         if ($result->count() > $bestMatch->count()) {
    //             $bestMatch = $result;
    //         }
    //     }

    //     // Final fallback
    //     return $bestMatch;
    // }

     public function getSuggestedPlays($league, Request $request)
    {  
        $possession = $request->input('possession');

        if ($possession === 'defensive') {
           return $this->getDefensivePlays($request);
        }

        return $this->getOffensivePlays($request);
     
    }


        protected function getOffensivePlays(Request $request)
    {
           
        $leagueId=$request->league_id;
        $matchId=$request->match_id;
              

        $query = Play::whereHas('configuredLeagues', function ($q) use ($leagueId,$matchId) {
              $q->where('configure_plays.league_id', $leagueId)->where('configure_plays.match_id', $matchId);
                    // ->orWhereIn('configure_plays.play_id', [1, 2, 3, 4]);
        });
 
      
         $id =  ['1',$request->league_id];
 
        $possession = $request->input('possession');
        $zone = $request->input('zone');
        $down = $request->input('down');

        $filters = [
            
            'preferred_down' => $request->input('down'),
            'possession'     => $request->input('possession'),
            'strategies'     => $request->input('strategy'),
            'min_expected_yard'     => $request->input('expectedyard'),
            // 'quarter'     => $request->input('quarter'),
        ];
      
        foreach ($filters as $field => $value) {
            if (!in_array($value, [null, '', 'null'], true)) {
                if ($field == 'preferred_down') {
                    $query->whereRaw("FIND_IN_SET(?, preferred_down)", [$value]);
                }else if($field == 'strategies'){
                    $query->whereRaw("FIND_IN_SET(?, strategies)", [$value]);
                } else {
                    $query->where($field, $value);
                }
            }
        }
 

    
        $plays = $query->inRandomOrder()->limit(3)->get();
        return response()->json($plays);
    }


      public function getDefensivePlays(Request $request)
        {
            $leagueId = $request->league_id;
            $matchId = $request->match_id;
            $inputGrouping = $request->opponent_personnel_grouping;
           
          
                $players = $request->input('player');

                if (is_array($players)) {
                    foreach ($players as $player) {
                        \Log::info('Player value: ' . ($player['value'] ?? 'N/A') . ', text: ' . ($player['text'] ?? 'N/A'));
                    }
                }
            // Step 1: Query the relevant records
            $plays = DefensivePlay::with('personals')->whereHas('configuredLeagues', function ($q) use ($leagueId, $matchId) {
                $q->where('configure_defensive_plays.league_id', $leagueId)
                ->where('configure_defensive_plays.game_id', $matchId);
            })->get();
            return response()->json($plays);
           
          

            return response()->json($filtered->values()); // return matched plays
        }

//     protected function getDefensivePlays(Request $request)
//     {
//         $leagueId = $request->league_id;
//         $matchId = $request->match_id;
// // player=268&opponent_personal_group=3
//         $query = DefensivePlay::whereHas('configuredLeagues', function ($q) use ($leagueId, $matchId) {
//             $q->where('configure_defensive_plays.league_id', $leagueId)
//             ->where('configure_defensive_plays.game_id', $matchId);
//         });

//         $filters = [
//             'coverage_type' => $request->input('coverage'),    // example
//             'rush_count'    => $request->input('rushCount'),   // example
//             'formation'     => $request->input('formation'),   // example
//         ];

//         foreach ($filters as $field => $value) {
//             if (!in_array($value, [null, '', 'null'], true)) {
//                 $query->where($field, $value);
//             }
//         }

//         return response()->json($query->inRandomOrder()->limit(3)->get());
//     }

}
