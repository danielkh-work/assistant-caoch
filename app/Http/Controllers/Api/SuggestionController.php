<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Play;
use App\Models\OpponentTeamPackage;
use App\Models\DefensivePlay;
use Illuminate\Http\Request;
use App\Models\BenchPlayer;
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
       
        $players = BenchPlayer::where('game_id', $request->match_id)->get();
       
        
        $myTeamPlayerIds = $players
            ->where('type', 'myteam')
            ->pluck('player_id')
            ->values()
            ->toArray();

        $opponentPlayerIds = $players
            ->where('type', 'opponent')
            ->pluck('player_id')
            ->values()
            ->toArray();
      
        $possession = $request->input('possession');
        
        if ($possession === 'defensive') {
          
           return $this->getDefensivePlays($request,$opponentPlayerIds);
        }
      
        return $this->getOffensivePlays($request,  $myTeamPlayerIds);
     
    }


        protected function getOffensivePlays(Request $request,$myTeamPlayerIds)
    {
           
        $leagueId=$request->league_id;
        $matchId=$request->match_id;
        $myTeamPlayerIds = $myTeamPlayerIds ?? [];
    
        $query = Play::with(['roles', 'playResults','personalGroupings'])->whereHas('configuredLeagues', function ($q) use ($leagueId,$matchId) {
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
 
        // if (!empty($myTeamPlayerIds)) {
        //     $query->withCount([
        //         'personalGroupings as matching_players_count' => function ($q) use ($myTeamPlayerIds) {
        //             foreach ($myTeamPlayerIds as $playerId) {
        //                 $q->orWhereJsonContains('players', (int) $playerId);
        //             }
        //         }
        //     ])
        //     ->orderByDesc('matching_players_count');
        // }
       
        \Log::info(['my team player ids'=>$myTeamPlayerIds]);
        if (!empty($myTeamPlayerIds)) {

                    $query->addSelect([
                        'best_group_match' => function ($sub) use ($myTeamPlayerIds) {

                            $matchExpression = collect($myTeamPlayerIds)
                                ->map(fn ($id) => "JSON_CONTAINS(personal_groupings.players, '[{$id}]')")
                                ->implode(' + ');

                            $sub->from('personal_groupings')
                                ->join(
                                    'personal_grouping_play',
                                    'personal_groupings.id',
                                    '=',
                                    'personal_grouping_play.personal_grouping_id'
                                )
                                ->whereColumn(
                                    'personal_grouping_play.play_id',
                                    'plays.id'
                                )
                                ->selectRaw("MAX($matchExpression)");
                        }
                    ])
                    ->orderByDesc('best_group_match');
            }

        
        $plays = $query->inRandomOrder()->limit(3)->withCount([
        'playResults as win_result' => function ($q) {
            $q->where('result', 'win')->where('is_practice', 0);
        },
        'playResults as win_result_rain' => function ($q) {
            $q->where('result', 'win')->where('weather', 'rain');
        },
        'playResults as win_result_snow' => function ($q) {
            $q->where('result', 'win')->where('weather', 'snow');
        },
      
        'playResults as loss_result' => function ($q) {
            $q->where('result', 'loss')->where('is_practice', 0);
        },
        'playResults as practice_win_result' => function ($q) {
            $q->where('result', 'win')->where('is_practice', 1);
        },
         'playResults as practice_loss_result' => function ($q) {
            $q->where('result', 'win')->where('is_practice', 1);
        },
        'playResults as total_count' => function ($q) {
            $q->where('is_practice', 0);
         },
          'playResults as total_practice_count' => function ($q) {
            $q->where('is_practice', 1);
         },
         'playResults as total_rain' => function ($q) {
            $q->where('weather', 'rain');
         },
          'playResults as total_snow' => function ($q) {
            $q->where('weather', 'snow');
         },
         
        
    ])
     ->withAvg('playResults as yardage_difference', 'yardage_difference')->get();

       \Log::info(['plays'=>$plays]);
        return response()->json($plays);
    }


public function getDefensivePlays(Request $request,$opponentPlayerIds)
{
    $leagueId = $request->input('league_id');
     $opponentPlayerIds = $opponentPlayerIds ?? [];
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
        'playResults',
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
    $defensivePlays = $query->withCount([
            'playResults as win_result' => function ($q) {
              $q->where('result', 'win')->where('is_practice', 0);
            },
            'playResults as practice_win_result' => function ($q) {
                $q->where('result', 'win')->where('is_practice', 1);
            },
            'playResults as win_result_rain' => function ($q) {
                $q->where('result', 'win')->where('weather', 'rain');
             },
            'playResults as win_result_snow' => function ($q) {
                $q->where('result', 'win')->where('weather', 'snow');
            },
            'playResults as total_rain' => function ($q) {
              $q->where('weather', 'rain');
             },
            'playResults as total_snow' => function ($q) {
             $q->where('weather', 'snow');
            },
            'playResults as loss_result' => function ($q) {
              $q->where('result', 'loss');
            },
            'playResults as total_count' => function ($q) {
                $q->where('is_practice', 0);
             },
            'playResults as total_practice_count' => function ($q) {
               $q->where('is_practice', 1);
             },
            ])
            
             ->withAvg('playResults as yardage_difference', 'yardage_difference') ->get();

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
