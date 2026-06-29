<?php

namespace App\Support;

use App\Events\MobileSessionLogout;
use App\Models\Device;
use App\Models\MobileSession;
use Illuminate\Support\Facades\Schema;

class DeviceMobileSession
{
    public static function bind(Device $device, string $sessionId): void
    {
        $device->session_id = $sessionId;
        $device->save();

        if (! Schema::hasTable('mobile_sessions')) {
            return;
        }

        MobileSession::updateOrCreate(
            ['session_id' => $sessionId],
            ['mobile_user_id' => null, 'status' => 'approved'],
        );
    }

    /**
     * @return list<string>
     */
    public static function collectLogoutSessionIds(Device $device): array
    {
        $linked = Schema::hasTable('mobile_sessions')
            ? MobileSession::query()
                ->where('session_id', $device->session_id)
                ->pluck('session_id')
            : collect();

        return collect([$device->session_id])
            ->merge($linked)
            ->filter(fn ($id) => is_string($id) && $id !== '')
            ->unique()
            ->values()
            ->all();
    }

    public static function logoutAndBroadcast(Device $device): void
    {
        $sessionIds = self::collectLogoutSessionIds($device);
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

        $device->session_id = null;
        $device->save();
    }
}
