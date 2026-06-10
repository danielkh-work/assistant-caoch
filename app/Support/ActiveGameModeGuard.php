<?php

namespace App\Support;

use App\Models\PlayGameMode;
use App\Models\User;
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

    public static function assertCanStart(int $headCoachId, bool $isPractice): void
    {
        self::assertNoOtherModeActive($headCoachId, $isPractice);

        $targetMode = self::targetMode($isPractice);

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
    }

    public static function completeSession(int $headCoachId, int $gameId): void
    {
        PlayGameMode::query()
            ->whereKey($gameId)
            ->where('user_id', $headCoachId)
            ->where('status', self::STATUS_ACTIVE)
            ->update(['status' => self::STATUS_COMPLETED]);
    }
}
