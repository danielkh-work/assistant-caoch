<?php

namespace App\Support;

use App\Models\PlayGameMode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ActiveGameModeGuard
{
    public const STATUS_ACTIVE = 2;

    public const STATUS_COMPLETED = 4;

    public static function resolveHeadCoachId(User $user): int
    {
        if ($user->role === 'head_coach') {
            return (int) $user->id;
        }

        if ($user->head_coach_id) {
            return (int) $user->head_coach_id;
        }

        abort(403, 'Head coach context is required.');
    }

    public static function targetMode(bool $isPractice): string
    {
        return $isPractice ? 'practice' : 'play';
    }

    public static function activeSession(int $headCoachId, string $gameMode): ?PlayGameMode
    {
        return PlayGameMode::query()
            ->where('user_id', $headCoachId)
            ->where('status', self::STATUS_ACTIVE)
            ->where('game_mode', $gameMode)
            ->latest('updated_at')
            ->first();
    }

    /**
     * Complete same-mode sessions that have no live scoreboard row (orphaned DB rows only).
     * Never touches the other game_mode — that would bypass cross-mode validation.
     */
    public static function reconcileOrphanedSessionsForMode(int $headCoachId, string $gameMode): void
    {
        $sessions = PlayGameMode::query()
            ->where('user_id', $headCoachId)
            ->where('status', self::STATUS_ACTIVE)
            ->where('game_mode', $gameMode)
            ->get();

        foreach ($sessions as $session) {
            if (! self::sessionHasLiveScoreboard($headCoachId, $session)) {
                PlayGameMode::query()
                    ->whereKey($session->id)
                    ->where('status', self::STATUS_ACTIVE)
                    ->update(['status' => self::STATUS_COMPLETED]);
            }
        }
    }

    private static function sessionHasLiveScoreboard(int $headCoachId, PlayGameMode $session): bool
    {
        $table = $session->game_mode === 'practice'
            ? 'websocket_practice_scoreboards'
            : 'websocket_scoreboards';

        if (! Schema::hasTable($table)) {
            return false;
        }

        $query = DB::table($table)
            ->where('user_id', $headCoachId)
            ->where('is_start', true);

        if (Schema::hasColumn($table, 'session_id')) {
            $query->where('session_id', $session->id);
        } elseif ($session->league_id) {
            $query->where('league_id', $session->league_id);
        } else {
            return false;
        }

        return $query->exists();
    }

    public static function assertCanStart(int $headCoachId, bool $isPractice): void
    {
        $otherMode = $isPractice ? 'play' : 'practice';
        self::reconcileOrphanedSessionsForMode($headCoachId, $otherMode);

        self::assertNoOtherModeActive($headCoachId, $isPractice);

        $targetMode = self::targetMode($isPractice);
        self::reconcileOrphanedSessionsForMode($headCoachId, $targetMode);

        if (self::activeSession($headCoachId, $targetMode)) {
            throw ValidationException::withMessages([
                'game_mode' => $isPractice
                    ? 'Practice mode is already in progress. Please end it before starting a new session.'
                    : 'Game mode is already in progress. Please end it before starting a new session.',
            ]);
        }
    }

    public static function assertNoOtherModeActive(int $headCoachId, bool $isPractice): void
    {
        $otherMode = $isPractice ? 'play' : 'practice';

        if (self::activeSession($headCoachId, $otherMode)) {
            throw ValidationException::withMessages([
                'game_mode' => $isPractice
                    ? 'Game mode is in progress. Please end it before starting practice mode.'
                    : 'Practice mode is in progress. Please end it before starting game mode.',
            ]);
        }

        if (self::scoreboardLiveForMode($headCoachId, $otherMode)) {
            throw ValidationException::withMessages([
                'game_mode' => $isPractice
                    ? 'Game mode is in progress. Please end it before starting practice mode.'
                    : 'Practice mode is in progress. Please end it before starting game mode.',
            ]);
        }
    }

    public static function scoreboardLiveForMode(int $headCoachId, string $gameMode): bool
    {
        $table = $gameMode === 'practice'
            ? 'websocket_practice_scoreboards'
            : 'websocket_scoreboards';

        if (! Schema::hasTable($table)) {
            return false;
        }

        $rows = DB::table($table)
            ->where('user_id', $headCoachId)
            ->where('is_start', true)
            ->get();

        foreach ($rows as $row) {
            if (self::scoreboardIndicatesLive($row, $headCoachId, $gameMode)) {
                return true;
            }

            self::clearStaleScoreboardRow($row, $table);
        }

        return false;
    }

    public static function scoreboardIndicatesLive(object $row, int $headCoachId, string $gameMode): bool
    {
        if (! $row->is_start) {
            return false;
        }

        if (($row->action ?? null) === 'EndMatch') {
            return false;
        }

        $baseQuery = PlayGameMode::query()
            ->where('user_id', $headCoachId)
            ->where('status', self::STATUS_ACTIVE)
            ->where('game_mode', $gameMode);

        if (! empty($row->session_id)) {
            if ((clone $baseQuery)->where('id', $row->session_id)->exists()) {
                return true;
            }
        }

        if (! empty($row->league_id)) {
            return (clone $baseQuery)->where('league_id', $row->league_id)->exists();
        }

        return false;
    }

    public static function clearStaleScoreboardRow(object $row, string $table): void
    {
        DB::table($table)
            ->where('id', $row->id)
            ->update([
                'is_start' => false,
                'action' => 'INFO',
                'updated_at' => now(),
            ]);
    }

    public static function reconcileScoreboardRow(?object $row, int $headCoachId, string $gameMode, string $table): ?object
    {
        if (! $row) {
            return null;
        }

        if (isset($row->updated_at)) {
            $updatedAt = $row->updated_at instanceof \DateTimeInterface
                ? $row->updated_at
                : \Carbon\Carbon::parse($row->updated_at);

            if ($updatedAt->diffInSeconds(now()) < 30) {
                return $row;
            }
        }

        if (! self::scoreboardIndicatesLive($row, $headCoachId, $gameMode)) {
            if ($row->is_start) {
                self::clearStaleScoreboardRow($row, $table);
            }

            return null;
        }

        return $row;
    }

    public static function completeSession(int $headCoachId, int $sessionId): void
    {
        PlayGameMode::query()
            ->whereKey($sessionId)
            ->where('user_id', $headCoachId)
            ->where('status', self::STATUS_ACTIVE)
            ->update(['status' => self::STATUS_COMPLETED]);
    }

    public static function completeActiveSessionsForMode(int $headCoachId, string $gameMode, ?int $leagueId = null): void
    {
        $query = PlayGameMode::query()
            ->where('user_id', $headCoachId)
            ->where('status', self::STATUS_ACTIVE)
            ->where('game_mode', $gameMode);

        if ($leagueId) {
            $query->where('league_id', $leagueId);
        }

        $query->update(['status' => self::STATUS_COMPLETED]);
    }
}
