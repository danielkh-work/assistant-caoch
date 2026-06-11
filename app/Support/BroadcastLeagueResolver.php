<?php

namespace App\Support;

use App\Models\PlayGameMode;
use Illuminate\Http\Request;

class BroadcastLeagueResolver
{
    public static function fromRequest(Request $request, ?array $nested = null): ?int
    {
        $candidates = [
            $request->input('league_id'),
            $nested['league_id'] ?? null,
            $request->input('suggestionData.league_id'),
        ];

        if (is_array($request->input('suggestionData'))) {
            $candidates[] = $request->input('suggestionData')['league_id'] ?? null;
        }

        foreach ($candidates as $value) {
            if ($value !== null && $value !== '' && is_numeric($value)) {
                return (int) $value;
            }
        }

        $gameId = $request->input('game_id')
            ?? ($nested['game_id'] ?? null)
            ?? (is_array($request->input('suggestionData')) ? ($request->input('suggestionData')['game_id'] ?? null) : null);

        if ($gameId !== null && $gameId !== '' && is_numeric($gameId)) {
            return self::fromGameId((int) $gameId);
        }

        return null;
    }

    public static function fromGameId(int $gameId): ?int
    {
        $leagueId = PlayGameMode::query()
            ->whereKey($gameId)
            ->value('league_id');

        return $leagueId !== null ? (int) $leagueId : null;
    }
}
