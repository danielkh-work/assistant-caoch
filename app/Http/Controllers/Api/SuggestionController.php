<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Play;
use App\Models\OpponentTeamPackage;
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
          \Log::info(['possession'=>$possession ]);
        if ($possession === 'defensive') {
           \Log::info(['possession defensive'=>$possession ]);
           return $this->getDefensivePlays($request);
        }
         \Log::info(['possession offensive'=>$possession ]);
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
    $leagueId = $request->input('league_id');

    // Step 1: Get player IDs from opponent package
    $playerIds = \DB::table('opponent_package_player')
        ->where('opponent_team_package_id', $request->input('pkg'))
        ->pluck('player_id')
        ->toArray();

    // Step 2: Check if any DefensivePlay matches the playerIds
    $hasMatchingPlayers = false;

    if (!empty($playerIds)) {
        $matchingCount = DefensivePlay::whereHas('personals', function ($query) use ($playerIds) {
            $query->whereIn('teamplayer_id', $playerIds);
        })
        ->where('league_id', $leagueId)
        ->count();

        if ($matchingCount > 0) {
            $hasMatchingPlayers = true;
        }
    }

    // Step 3: Base query with eager loading
    $query = DefensivePlay::with([
        'strategyBlitz',
        'formation',
        'personals.teamPlayer.player',
        'personals'
    ])->where('league_id', $leagueId);

    // Step 4: If matching players found, filter by them
    if ($hasMatchingPlayers) {
        $query->whereHas('personals', function ($subQuery) use ($playerIds) {
            $subQuery->whereIn('teamplayer_id', $playerIds);
        });
    } else {
        // Step 5: Fallback to parameter-based filters
        $filters = [
            'preferred_down'      => $request->input('down'),
          
            'strategies'          => $request->input('strategy'),
            'min_expected_yard'      => $request->input('expectedyard'), // actual column name
        ];

        foreach ($filters as $field => $value) {
            if (!in_array($value, [null, '', 'null'], true)) {
                switch ($field) {
                    case 'preferred_down':
                    case 'strategies':
                        // Match comma-separated values
                        $query->whereRaw("FIND_IN_SET(?, $field)", [$value]);
                        break;

                    default:
                        $query->where($field, $value);
                        break;
                }
            }
        }
    }

    // Step 6: Fetch results
    $defensivePlays = $query->get();

    return response()->json($defensivePlays);
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
