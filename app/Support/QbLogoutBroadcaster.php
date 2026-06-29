<?php

namespace App\Support;

use App\Events\MobileSessionLogout;
use App\Events\QbSessionUpdated;
use App\Models\User;

class QbLogoutBroadcaster
{
    /**
     * @param  list<string>  $extraSessionIds
     * @return array<string, mixed>
     */
    public static function logoutAndBroadcast(User $user, User $coach, array $extraSessionIds = []): array
    {
        $sessionIds = collect(QbMobileSession::collectLogoutSessionIds($user))
            ->merge($extraSessionIds)
            ->filter(fn ($id) => is_string($id) && $id !== '')
            ->unique()
            ->values()
            ->all();

        $user->is_loggin = false;
        $user->session_id = null;
        $user->save();

        $userFields = $user->only([
            'id',
            'name',
            'email',
            'role',
            'session_id',
            'code',
            'head_coach_id',
            'league_id',
            'team_id',
            'is_loggin',
        ]);

        $payload = [
            'status' => 200,
            'message' => 'logout successful',
            'user' => $userFields,
            'is_loggin' => false,
        ];

        $userSnapshot = $user->only(['id', 'name', 'code', 'head_coach_id', 'league_id', 'team_id']);

        foreach ($sessionIds as $sessionId) {
            broadcast(new MobileSessionLogout([
                'status' => 200,
                'message' => 'logout successful',
                'user' => array_merge($userSnapshot, ['session_id' => $sessionId]),
            ], $sessionId))->toOthers();
        }

        broadcast(new QbSessionUpdated(
            (int) $coach->id,
            (int) ($user->league_id ?? 0),
            $userFields,
            false,
            'logout',
        ))->toOthers();

        return $payload;
    }
}
