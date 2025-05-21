<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Play;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    public function getSuggestedPlays($league, Request $request)
    {
        $zone = $this->getZoneFromYardLine($yardLine);

        return Play::where('zone_selection', $zone)
            ->whereJsonContains('preferred_downs', $down)
            ->where('side', $possession) // offense or defense
            ->orderByRaw('RAND()') // randomize or sort by priority later
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
