<?php

namespace App\Support;

use App\Models\PlayGameMode;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ActiveGameModeGuard
{
    public const STATUS_ACTIVE = 2;

    public const STATUS_COMPLETED = 4;

    public static function liveSessionTimeoutMinutes(): int
    {
        return max(1, (int) config('game_modes.live_session_timeout_minutes', 30));
    }

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

    public static function activeSession(int $headCoachId, string $gameMode, ?int $leagueId = null): ?PlayGameMode
    {
        $query = PlayGameMode::query()
            ->where('user_id', $headCoachId)
            ->where('status', self::STATUS_ACTIVE)
            ->where('game_mode', $gameMode);

        if ($leagueId !== null) {
            $query->where('league_id', $leagueId);
        }

        return $query->latest('updated_at')->first();
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
                    ->update([
                        'status' => self::STATUS_COMPLETED,
                        'updated_at' => now(),
                    ]);
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

        $rows = $query->get();

        foreach ($rows as $row) {
            if (self::expireStaleScoreboardRowIfNeeded($row, $headCoachId, $session->game_mode, $table)) {
                continue;
            }

            if (self::scoreboardIndicatesLive($row, $headCoachId, $session->game_mode)) {
                return true;
            }
        }

        return false;
    }

    public static function assertCanStart(int $headCoachId, bool $isPractice, ?int $leagueId = null): void
    {
        self::expireStaleSessions($headCoachId, null, $leagueId);

        $otherMode = $isPractice ? 'play' : 'practice';
        self::reconcileOrphanedSessionsForMode($headCoachId, $otherMode);

        self::assertNoOtherModeActive($headCoachId, $isPractice, $leagueId);

        $targetMode = self::targetMode($isPractice);
        self::reconcileOrphanedSessionsForMode($headCoachId, $targetMode);

        if (self::activeSession($headCoachId, $targetMode, $leagueId)) {
            throw ValidationException::withMessages([
                'game_mode' => $isPractice
                    ? 'Practice mode is already in progress. Please end it before starting a new session.'
                    : 'Game mode is already in progress. Please end it before starting a new session.',
            ]);
        }
    }

    public static function assertNoOtherModeActive(int $headCoachId, bool $isPractice, ?int $leagueId = null): void
    {
        self::expireStaleSessions($headCoachId, null, $leagueId);

        $otherMode = $isPractice ? 'play' : 'practice';
        self::reconcileOrphanedSessionsForMode($headCoachId, $otherMode);

        $conflictSession = self::activeSession($headCoachId, $otherMode, $leagueId);

        if ($conflictSession) {
            throw ValidationException::withMessages([
                'game_mode' => $isPractice
                    ? 'Game mode is in progress. Please end it before starting practice mode.'
                    : 'Practice mode is in progress. Please end it before starting game mode.',
            ]);
        }

        $scoreboardLive = self::scoreboardLiveForMode($headCoachId, $otherMode, $leagueId);

        if ($scoreboardLive) {
            throw ValidationException::withMessages([
                'game_mode' => $isPractice
                    ? 'Game mode is in progress. Please end it before starting practice mode.'
                    : 'Practice mode is in progress. Please end it before starting game mode.',
            ]);
        }
    }

    public static function scoreboardLiveForMode(int $headCoachId, string $gameMode, ?int $leagueId = null): bool
    {
        $table = $gameMode === 'practice'
            ? 'websocket_practice_scoreboards'
            : 'websocket_scoreboards';

        if (! Schema::hasTable($table)) {
            return false;
        }

        $hasLeagueCol = Schema::hasColumn($table, 'league_id');

        $query = DB::table($table)
            ->where('user_id', $headCoachId)
            ->where('is_start', true);

        if ($leagueId !== null && $hasLeagueCol) {
            $query->where('league_id', $leagueId);
        }

        $rows = $query->get();

        foreach ($rows as $row) {
            if (self::expireStaleScoreboardRowIfNeeded($row, $headCoachId, $gameMode, $table)) {
                continue;
            }

            if (self::scoreboardIndicatesLive($row, $headCoachId, $gameMode)) {
                return true;
            }

            self::clearStaleScoreboardRow($row, $table);
        }

        return false;
    }

    public static function expireStaleSessions(?int $headCoachId = null, ?string $gameMode = null, ?int $leagueId = null): int
    {
        $modes = $gameMode ? [$gameMode] : ['play', 'practice'];
        $expired = 0;

        foreach ($modes as $mode) {
            $table = $mode === 'practice'
                ? 'websocket_practice_scoreboards'
                : 'websocket_scoreboards';

            if (! Schema::hasTable($table)) {
                continue;
            }

            $query = DB::table($table)
                ->where('is_start', true)
                ->where('updated_at', '<=', now()->subMinutes(self::liveSessionTimeoutMinutes()));

            if ($headCoachId !== null) {
                $query->where('user_id', $headCoachId);
            }

            if ($leagueId !== null && Schema::hasColumn($table, 'league_id')) {
                $query->where('league_id', $leagueId);
            }

            foreach ($query->get() as $row) {
                if (self::expireStaleScoreboardRowIfNeeded($row, (int) $row->user_id, $mode, $table)) {
                    $expired++;
                }
            }
        }

        return $expired;
    }

    public static function expireStaleScoreboardRowIfNeeded(object $row, int $headCoachId, string $gameMode, string $table): bool
    {
        if (! ($row->is_start ?? false) || ! self::scoreboardRowIsStale($row)) {
            return false;
        }

        if (! empty($row->session_id)) {
            self::completeSession($headCoachId, (int) $row->session_id);
        } elseif (! empty($row->league_id)) {
            self::completeActiveSessionsForMode($headCoachId, $gameMode, (int) $row->league_id);
        } else {
            self::completeActiveSessionsForMode($headCoachId, $gameMode);
        }

        self::clearStaleScoreboardRow($row, $table);

        return true;
    }

    private static function scoreboardRowIsStale(object $row): bool
    {
        if (empty($row->updated_at)) {
            return false;
        }

        return Carbon::parse($row->updated_at)->lte(
            now()->subMinutes(self::liveSessionTimeoutMinutes())
        );
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
        $values = [
            'is_start' => false,
            'action' => 'INFO',
            'updated_at' => now(),
        ];

        if (Schema::hasColumn($table, 'sys_time')) {
            $values['sys_time'] = now()->toDateTimeString();
        }

        DB::table($table)
            ->where('id', $row->id)
            ->update($values);
    }

    public static function reconcileScoreboardRow(?object $row, int $headCoachId, string $gameMode, string $table): ?object
    {
        if (! $row) {
            return null;
        }

        if (self::expireStaleScoreboardRowIfNeeded($row, $headCoachId, $gameMode, $table)) {
            return null;
        }

        if (! self::scoreboardIndicatesLive($row, $headCoachId, $gameMode)) {
            if ($row->is_start) {
                self::clearStaleScoreboardRow($row, $table);
            }

            return null;
        }

        return $row;
    }

    public static function touchLiveScoreboardRow(object $row, string $table): void
    {
        if (! ($row->is_start ?? false)) {
            return;
        }

        $values = ['updated_at' => now()];

        if (Schema::hasColumn($table, 'sys_time')) {
            $values['sys_time'] = now()->toDateTimeString();
        }

        DB::table($table)
            ->where('id', $row->id)
            ->update($values);
    }

    public static function completeSession(int $headCoachId, int $sessionId): void
    {
        PlayGameMode::query()
            ->whereKey($sessionId)
            ->where('user_id', $headCoachId)
            ->where('status', self::STATUS_ACTIVE)
            ->update([
                'status' => self::STATUS_COMPLETED,
                'updated_at' => now(),
            ]);
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

        $query->update([
            'status' => self::STATUS_COMPLETED,
            'updated_at' => now(),
        ]);
    }
}
