<?php

namespace App\Support;

use App\Events\QbSessionUpdated;
use App\Models\Device;

class DeviceSessionBroadcaster
{
    /**
     * @return array<string, mixed>
     */
    public static function deviceFields(Device $device): array
    {
        return [
            'id' => $device->id,
            'device_id' => $device->device_id,
            'device_name' => $device->device_name,
            'pairing_code' => $device->pairing_code,
            'status' => $device->status,
            'team_id' => $device->team_id,
            'user_id' => $device->user_id,
            'session_id' => $device->session_id,
            'paired_at' => $device->paired_at?->toIso8601String(),
            'is_connected' => $device->tokens()->exists(),
        ];
    }

    /**
     * Legacy QB web/mobile field shape used by `QbSessionUpdated` listeners.
     *
     * @return array<string, mixed>
     */
    public static function legacyWebFields(Device $device, int $leagueId, bool $isLoggedIn): array
    {
        return [
            'id' => $device->id,
            'device_id' => $device->device_id,
            'name' => $device->device_name,
            'email' => '',
            'role' => 'device',
            'session_id' => $device->session_id,
            'code' => $device->pairing_code,
            'head_coach_id' => $device->user_id,
            'league_id' => $leagueId,
            'team_id' => $device->team_id,
            'is_loggin' => $isLoggedIn,
            'device_name' => $device->device_name,
            'pairing_code' => $device->pairing_code,
            'status' => $device->status,
        ];
    }

    public static function broadcastForLeagues(Device $device, string $action, bool $isLoggedIn): void
    {
        $device->loadMissing('leagues', 'user');
        $headCoachId = (int) ($device->user_id ?? 0);

        if ($headCoachId === 0) {
            return;
        }

        foreach ($device->leagues as $league) {
            $userFields = self::legacyWebFields($device, (int) $league->id, $isLoggedIn);

            broadcast(new QbSessionUpdated(
                $headCoachId,
                (int) $league->id,
                $userFields,
                $isLoggedIn,
                $action,
            ))->toOthers();
        }
    }
}
