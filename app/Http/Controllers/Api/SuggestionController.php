<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Play;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    public function getSuggestedPlays($league, Request $request)
    {
        return Play::where('zone_selection', $request->zone)
            ->where('preferred_down', $request->down)
            ->where('possession', $request->possession)
            ->take(3)
            ->get();
    }

    function getZoneFromYardLine($yardLine) {
        if ($yardLine >= 1 && $yardLine <= 20) return 'Zone 1';
        if ($yardLine >= 21 && $yardLine <= 50) return 'Zone 2';
        if ($yardLine >= 51 && $yardLine <= 80) return 'Zone 3';
        if ($yardLine >= 81 && $yardLine <= 100) return 'Zone 4';
        return null;
    }
}
