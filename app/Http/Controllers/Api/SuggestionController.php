<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Play;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    public function getSuggestedPlays($league, Request $request)
    {
        $validated = $request->validate([
            'zone' => 'required|integer',
            'down' => 'required|integer|in:1,2,3,4',
            'possession' => 'required|string|in:offensive,defensive',
        ]);

        // Step 1: Match all three
        $plays = Play::where('zone_selection', $validated['zone'])
            ->where('preferred_down', $validated['down'])
            ->where('possession', $validated['possession'])
            ->take(3)
            ->get();

        if ($plays->count() >= 3) {
            return $plays;
        }

        // Step 2: Match any two
        $bestMatch = collect(); // Will hold the best fallback result

        $combinations = [
            ['zone_selection', 'preferred_down'],
            ['zone_selection', 'possession'],
            ['preferred_down', 'possession'],
        ];

        foreach ($combinations as $combo) {
            $query = Play::query();

            foreach ($combo as $field) {
                if ($field === 'zone_selection') {
                    $query->where($field, $validated['zone']);
                } elseif ($field === 'preferred_down') {
                    $query->where($field, $validated['down']);
                } else {
                    $query->where($field, $validated['possession']);
                }
            }

            $result = $query->take(3)->get();
            if ($result->count() >= 3) {
                return $result;
            }

            // Store the best result so far
            if ($result->count() > $bestMatch->count()) {
                $bestMatch = $result;
            }
        }

        // Step 3: Match any one
        $fields = [
            'zone_selection' => $validated['zone'],
            'preferred_down' => $validated['down'],
            'possession' => $validated['possession'],
        ];

        foreach ($fields as $field => $value) {
            $result = Play::where($field, $value)->take(3)->get();
            if ($result->count() >= 3) {
                return $result;
            }

            if ($result->count() > $bestMatch->count()) {
                $bestMatch = $result;
            }
        }

        // Final fallback
        return $bestMatch;
    }
}
