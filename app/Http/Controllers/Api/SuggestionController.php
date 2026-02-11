<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Play;
use App\Models\Game;
use App\Models\BenchPlayer;
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
    $leagueId = $request->league_id;
    $matchId = $request->match_id;
    $gameData=Game::find($matchId);
    	
	// 8	oponent_team_id

  

    // Sample offensive players
    // $offenseByPosition = collect([
    //     ['id'=>1,'name'=>'John Doe','number'=>'22','position'=>'RB','position_value'=>'Running Back','rpp'=>8,'ofp'=>85,'speed'=>88,'strength'=>82],
    //     ['id'=>2,'name'=>'Mike Smith','number'=>'11','position'=>'WR','position_value'=>'Wide receiver W','rpp'=>7,'ofp'=>83,'speed'=>91,'strength'=>75],
    //     ['id'=>3,'name'=>'Alex Brown','number'=>'9','position'=>'QB','position_value'=>'Fullback','rpp'=>9,'ofp'=>90,'speed'=>78,'strength'=>80],
    //     ['id'=>3,'name'=>'Alex Brown','number'=>'9','position'=>'QB','position_value'=>'Center','rpp'=>9,'ofp'=>90,'speed'=>78,'strength'=>80],
    // ])->groupBy('position_value'); 

    // Fetch dynamic offensive players from the database
$offenseByPosition = BenchPlayer::with('player.player')
    ->where('game_id', $matchId)
    ->where('team_id', $gameData->my_team_id)
    ->where('type', 'myteam')
    ->where('player_type', 'offence')
    ->get()

    ->filter(fn($benchPlayer) => $benchPlayer->player && $benchPlayer->player->player)
    ->map(fn($benchPlayer) => [
        'id' => $benchPlayer->player->id,
        'name' => $benchPlayer->player->player->name,
        'number' => $benchPlayer->player->number,
        'size' => $benchPlayer->player->size,
        'position_value' => $benchPlayer->player->position_value,
        'squad' => 3,
        'position' => $benchPlayer->player->position,
        'speed' => $benchPlayer->player->speed,
        'strength' => $benchPlayer->player->strength,
        'ofp' => $benchPlayer->player->ofp,
        'rpp' => $benchPlayer->rpp,
        'weight' => $benchPlayer->player->weight,
        'height' => $benchPlayer->player->height,
        'dob' => $benchPlayer->player->player->dob,
    ])
    ->groupBy('position_value');


    $defenseByPosition = BenchPlayer::with('player.player')
    ->where('game_id', $matchId)
    ->where('team_id', $gameData->oponent_team_id)
    ->where('type', 'opponent')
    ->where('player_type', 'deffence')
    ->get()
    ->filter(fn($benchPlayer) => $benchPlayer->player && $benchPlayer->player->player) // ensure nested player exists
    ->map(fn($benchPlayer) => [
        'id' => $benchPlayer->player->id,
       
        'name' => $benchPlayer->player->player->name,
        'number' => $benchPlayer->player->number,
        'size' => $benchPlayer->player->size,
        'squad' => 3,
        'position_value' => $benchPlayer->player->position_value,
        'position' => $benchPlayer->player->position,
        'speed' => $benchPlayer->player->speed,
        'strength' => $benchPlayer->player->strength,
        'ofp' => $benchPlayer->player->ofp,
        'rpp' => $benchPlayer->rpp,
        'weight' => $benchPlayer->player->weight,
        'height' => $benchPlayer->player->height,
        'dob' => $benchPlayer->player->player->dob,
    ])
    ->groupBy('position_value'); 

      
     


    // Sample defensive players
    // $defenseByPosition = collect([
    //     ['id'=>4,'name'=>'Chris Wilson','number'=>'54','position'=>'LB','position_value'=>'Defensive End','rpp'=>8,'ofp'=>87,'speed'=>84,'strength'=>88],
    //     ['id'=>5,'name'=>'David Johnson','number'=>'23','position'=>'CB','position_value'=>'Defensive Tackle','rpp'=>6,'ofp'=>80,'speed'=>92,'strength'=>70],
    //     ['id'=>6,'name'=>'Samuel Green','number'=>'92','position'=>'DE','position_value'=>'Cornerback','rpp'=>7,'ofp'=>82,'speed'=>85,'strength'=>90],
    //     ['id'=>6,'name'=>'Samuel Green','number'=>'92','position'=>'DE','position_value'=>'Nose Tackle','rpp'=>7,'ofp'=>82,'speed'=>85,'strength'=>90],
    // ])->groupBy('position_value');

    // Build the query for plays
    $query = Play::with(['roles','playResults','offensiveTargets.offensivePosition','offensiveTargets.defensivePosition'])
        ->whereHas('configuredLeagues', function ($q) use ($leagueId,$matchId) {
            $q->where('configure_plays.league_id', $leagueId)
              ->where('configure_plays.match_id', $matchId);
        });

    // Apply filters
    $filters = [
        'preferred_down' => $request->input('down'),
        'possession' => $request->input('possession'),
        'strategies' => $request->input('strategy'),
        'min_expected_yard' => $request->input('expectedyard'),
    ];

    foreach ($filters as $field => $value) {
        if (!in_array($value, [null, '', 'null'], true)) {
            if ($field == 'preferred_down' || $field == 'strategies') {
                $query->whereRaw("FIND_IN_SET(?, $field)", [$value]);
            } else {
                $query->where($field, $value);
            }
        }
    }

    // Get plays with counts and averages
    $plays = $query->inRandomOrder()->limit(6)->withCount([
        'playResults as win_result' => fn($q)=>$q->where('result','win')->where('is_practice',0),
        'playResults as win_result_rain' => fn($q)=>$q->where('result','win')->where('weather','rain'),
        'playResults as win_result_snow' => fn($q)=>$q->where('result','win')->where('weather','snow'),
        'playResults as loss_result' => fn($q)=>$q->where('result','loss')->where('is_practice',0),
        'playResults as practice_win_result' => fn($q)=>$q->where('result','win')->where('is_practice',1),
        'playResults as practice_loss_result' => fn($q)=>$q->where('result','loss')->where('is_practice',1),
        'playResults as total_count' => fn($q)=>$q->where('is_practice',0),
        'playResults as total_practice_count' => fn($q)=>$q->where('is_practice',1),
        'playResults as total_rain' => fn($q)=>$q->where('weather','rain'),
        'playResults as total_snow' => fn($q)=>$q->where('weather','snow'),
    ])->withAvg('playResults as yardage_difference', 'yardage_difference')->get();

    // Map matchups
 $plays = $plays->map(function($play) use ($offenseByPosition, $defenseByPosition) {

    // Build matchups
    $matchups = $play->offensiveTargets->map(function($target) use ($offenseByPosition, $defenseByPosition) {

        $offPosName = $target->offensivePosition->name;
        $strength = $target->strength;
        $defPosName = $target->defensivePosition->name;

        $offPlayers = $offenseByPosition->get($offPosName, collect());
        $defPlayers = $defenseByPosition->get($defPosName, collect());

        $offRpp = $offPlayers->sum('rpp');
        $defRpp = $defPlayers->sum('rpp');

       $rpp_difference = $offRpp - $defRpp;

        if ($defRpp > 0) {
            $ratio = $rpp_difference / $defRpp;
        } else {
            $ratio = 0; // or 1, depending on your game logic
        }

        $rpp_difference_percentage = $rpp_difference * $ratio ;
// $ratio

        $strength_percentage =  $strength / 100;

        return [
            'offensive_position' => $offPosName,
            'strength'=>$strength,
            'defensive_position' => $defPosName,
            'offensive_players' => $offPlayers,
            'defensive_players' => $defPlayers,
            'offensive_rpp' => $offRpp,
            'defensive_rpp' => $defRpp,
            'rpp_difference' => $rpp_difference,
            'strength_percentage' => $strength_percentage,
            'rpp_difference_percentage' => $rpp_difference_percentage,
        ];
    });
    $sumRppPercentageByOffense = $matchups->groupBy('offensive_position')->map(function($group, $offPosName) use ($offenseByPosition) { 
    $sum = $group->sum('rpp_difference_percentage');
    $strength = $group->first()['strength'] ?? 100;
    $strength_percentage = $strength / 100;
    return $sum * $strength_percentage;
    });

    $totalRppPercentage = $sumRppPercentageByOffense->sum();
    $play->matchups = $matchups;
    $play->rpp_percentage_sum_by_offense = $sumRppPercentageByOffense;
    $play->total_score = round($totalRppPercentage, 2);
    
   
    return $play;
});



    $plays = $plays->sortByDesc('total_score')->values();

    $topByScore = $plays->sortByDesc('total_score')->take(3);

    $remaining = $plays->diff($topByScore);

   
    $hasWins = $remaining->where('win_result', '>', 0)->count() > 0;

    if ($hasWins) {
    $topByWins = $remaining->sortByDesc('win_result')->take(3);
    } else {
    
    $topByWins = $remaining->sortByDesc('total_score')->take(3);
    }

    $finalPlays = $topByScore->concat($topByWins)->values();


    return response()->json($finalPlays);
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
