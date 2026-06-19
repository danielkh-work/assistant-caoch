<?php

namespace App\Support;

use App\Events\MobileSessionLogout;
use App\Events\QbSessionUpdated;
use App\Models\Device;
use App\Models\User;

class DeviceLogoutBroadcaster
{
    /**
     * @param  list<string>  $extraSessionIds
     * @return array<string, mixed>
     */
    public static function logoutAndBroadcast(Device $device, ?User $coach = null, array $extraSessionIds = []): array
    {
        $device->loadMissing('leagues', 'user');
        $coach ??= $device->user_id ? User::find($device->user_id) : null;

        $sessionIds = collect(DeviceMobileSession::collectLogoutSessionIds($device))
            ->merge($extraSessionIds)
            ->filter(fn ($id) => is_string($id) && $id !== '')
            ->unique()
            ->values()
            ->all();

        $device->tokens()->delete();
        $device->session_id = null;
        $device->save();

        $snapshot = [
            'id' => $device->id,
            'name' => $device->device_name,
            'code' => $device->pairing_code,
            'head_coach_id' => $device->user_id,
            'team_id' => $device->team_id,
        ];

        foreach ($sessionIds as $sessionId) {
            broadcast(new MobileSessionLogout([
                'status' => 200,
                'message' => 'logout successful',
                'user' => array_merge($snapshot, ['session_id' => $sessionId]),
            ], $sessionId))->toOthers();
        }

        if ($coach) {
            foreach ($device->leagues as $league) {
                $userFields = DeviceSessionBroadcaster::legacyWebFields(
                    $device->fresh(),
                    (int) $league->id,
                    false,
                );

                broadcast(new QbSessionUpdated(
                    (int) $coach->id,
                    (int) $league->id,
                    $userFields,
                    false,
                    'logout',
                ))->toOthers();
            }
        }

        $leagueId = (int) ($device->leagues->first()?->id ?? 0);
        $userFields = DeviceSessionBroadcaster::legacyWebFields($device->fresh(), $leagueId, false);

        return [
            'status' => 200,
            'message' => 'logout successful',
            'user' => $userFields,
            'is_loggin' => false,
        ];
    }
}
