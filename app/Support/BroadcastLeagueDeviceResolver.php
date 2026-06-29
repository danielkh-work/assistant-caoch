<?php

namespace App\Support;

use App\Models\Device;
use App\Models\PlayGameMode;

class BroadcastLeagueDeviceResolver
{
    public static function deviceIdForGame(int $gameId): ?int
    {
        $deviceId = PlayGameMode::query()
            ->whereKey($gameId)
            ->value('device_id');

        return $deviceId !== null ? (int) $deviceId : null;
    }

    /**
     * @return list<int>
     */
    public static function registeredDeviceIdsForLeague(int $leagueId): array
    {
        return Device::query()
            ->where('status', 'registered')
            ->whereHas('leagues', fn ($q) => $q->where('leagues.id', $leagueId))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public static function leagueIdForGame(int $gameId): ?int
    {
        return BroadcastLeagueResolver::fromGameId($gameId);
    }
}
