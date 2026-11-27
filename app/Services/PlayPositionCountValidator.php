<?php

namespace App\Services;

class PlayPositionCountValidator
{
    /**
     * How many of the given position => strength pairs actually have a
     * non-zero value - a play "uses" a position once it's been given any
     * weight at all, no matter how small.
     */
    public static function filledPositionCount(?array $positions): int
    {
        return collect($positions ?? [])
            ->filter(fn ($value) => (float) $value > 0)
            ->count();
    }

    /**
     * True when either side of the play uses more positions than its
     * player count (11 or 12) allows. A play with no player count yet isn't
     * restricted - that's the point of leaving old, untagged plays alone.
     */
    public static function exceedsLimit(?int $playerCount, ?array $offensive, ?array $defensive): bool
    {
        if (!$playerCount) {
            return false;
        }

        return self::filledPositionCount($offensive) > $playerCount
            || self::filledPositionCount($defensive) > $playerCount;
    }
}
