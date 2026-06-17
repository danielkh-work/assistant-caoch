<?php

namespace App\Support;

use App\Models\Device;
use App\Models\League;
use App\Models\PlayGameMode;
use App\Models\User;

class BroadcastChannelAuth
{
    public static function headCoachOwnsLeague(User $user, int $headCoachId, int $leagueId): bool
    {
        if ($user->role === 'head_coach' && (int) $user->id === $headCoachId) {
            return League::query()
                ->whereKey($leagueId)
                ->where('user_id', $headCoachId)
                ->exists();
        }

        if (in_array($user->role, ['assistant_coach', 'performance_coach'], true)
            && (int) $user->head_coach_id === $headCoachId) {
            return League::query()
                ->whereKey($leagueId)
                ->where('user_id', $headCoachId)
                ->exists();
        }

        return false;
    }

    public static function deviceBelongsToLeague(Device $device, int $leagueId): bool
    {
        return $device->status === 'registered'
            && $device->leagues()->where('leagues.id', $leagueId)->exists();
    }

    public static function deviceCanAccessGame(Device $device, int $deviceId, int $gameId): bool
    {
        if ((int) $device->id !== $deviceId) {
            return false;
        }

        $game = PlayGameMode::query()->whereKey($gameId)->first();

        if (! $game) {
            return false;
        }

        if ((int) $game->device_id === $deviceId) {
            return true;
        }

        return $game->league_id
            && self::deviceBelongsToLeague($device, (int) $game->league_id);
    }
}
