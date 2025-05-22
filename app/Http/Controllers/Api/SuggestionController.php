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
    
        return Play::where('zone_selection', $validated['zone'])
            ->where('preferred_down', $validated['down'])
            ->where('possession', $validated['possession'])
            ->take(3)
            ->get();
    }
}
