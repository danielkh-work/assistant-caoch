<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Play;
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

        $leagueId=$request->league_id;
        $query = Play::whereHas('configuredLeagues', function ($q) use ($leagueId) {
              $q->where('configure_plays.league_id', $leagueId);
                    // ->orWhereIn('configure_plays.play_id', [1, 2, 3, 4]);
        });
 
       Log::info(['play'=> $query->get()]);
         $id =  ['1',$request->league_id];
 
        $possession = $request->input('possession');
        $zone = $request->input('zone');
        $down = $request->input('down');

        $filters = [
            'zone_selection' => $request->input('zone'),
            'preferred_down' => $request->input('down'),
            'possession'     => $request->input('possession'),
        ];
 
        foreach ($filters as $field => $value) {
            if (!in_array($value, [null, '', 'null'], true)) {
                if ($field == 'preferred_down') {
                    // Use FIND_IN_SET for comma-separated values
                    $query->whereRaw("FIND_IN_SET(?, preferred_down)", [$value]);
                } else {
                    $query->where($field, $value);
                }
            }
        }
 

        // foreach ($filters as $field => $value) {
        //     if (!in_array($value, [null, '', 'null'], true)) {
        //         $query->where($field, $value);
        //     }
        // }
        $plays = $query->inRandomOrder()->limit(3)->get();
        return response()->json($plays);
    }
}
