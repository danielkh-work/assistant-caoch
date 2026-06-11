<?php

namespace App\Support;

use App\Models\MobileSession;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class QbMobileSession
{
    public static function bind(User $qb, string $sessionId): void
    {
        $qb->session_id = $sessionId;

        if (! Schema::hasTable('mobile_sessions')) {
            return;
        }

        MobileSession::updateOrCreate(
            ['session_id' => $sessionId],
            ['mobile_user_id' => $qb->id, 'status' => 'approved'],
        );
    }

    /**
     * Keep users.session_id aligned with the mobile app's current Pusher subscription.
     * Called when the app opens (create-session) or polls login status with a bearer token.
     */
    public static function refreshActiveSession(User $qb, string $sessionId): User
    {
        self::bind($qb, $sessionId);
        $qb->is_loggin = true;
        $qb->save();

        return $qb->fresh();
    }

    /**
     * @return list<string>
     */
    public static function collectLogoutSessionIds(User $user): array
    {
        $linked = Schema::hasTable('mobile_sessions')
            ? MobileSession::query()
                ->where('mobile_user_id', $user->id)
                ->pluck('session_id')
            : collect();

        return collect([$user->session_id])
            ->merge($linked)
            ->filter(fn ($id) => is_string($id) && $id !== '')
            ->unique()
            ->values()
            ->all();
    }
}
