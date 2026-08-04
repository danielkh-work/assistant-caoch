<?php

namespace App\Services;

use App\Models\League;
use Carbon\Carbon;

class GameClockService
{
    /**
     * Seconds in one quarter. Matches play-mode UI:
     * length_of_quarters (minutes) * 60.
     */
    public function quarterLengthSeconds(?League $league): int
    {
        $minutes = (int) ($league?->length_of_quarters ?: 15);
        if ($minutes <= 0) {
            $minutes = 15;
        }

        return $minutes * 60;
    }

    public function maxQuarters(?League $league): int
    {
        $max = (int) ($league?->number_of_quarters ?: 4);

        return $max > 0 ? $max : 4;
    }

    public function isPaused(?string $action): bool
    {
        $action = (string) $action;

        return strcasecmp($action, 'Stop') === 0
            || strcasecmp($action, 'EndMatch') === 0;
    }

    /**
     * Compute live quarter + remaining from an anchor snapshot.
     *
     * @param  array{quarter?: mixed, timer_remaining?: mixed, sys_time?: mixed, action?: mixed}  $row
     * @return array{quarter: int, timer_remaining: int, sys_time: string, exhausted: bool, advanced: bool, exhausted_at: ?string}
     */
    public function resolve(
        array $row,
        int $quarterLengthSeconds,
        int $maxQuarters,
        ?Carbon $now = null
    ): array {
        $now = $now ?? Carbon::now();
        if ($quarterLengthSeconds <= 0) {
            $quarterLengthSeconds = 15 * 60;
        }
        if ($maxQuarters <= 0) {
            $maxQuarters = 4;
        }

        $quarter = max(1, (int) ($row['quarter'] ?? 1));
        $hasRemaining = array_key_exists('timer_remaining', $row) && $row['timer_remaining'] !== null;
        $remaining = $hasRemaining
            ? max(0, (int) $row['timer_remaining'])
            : $quarterLengthSeconds;
        $sysTimeRaw = $row['sys_time'] ?? null;
        $action = isset($row['action']) ? (string) $row['action'] : null;
        $nowString = $now->toDateTimeString();

        if ($this->isPaused($action)) {
            return [
                'quarter' => min($quarter, $maxQuarters),
                'timer_remaining' => $remaining,
                'sys_time' => $sysTimeRaw
                    ? Carbon::parse($sysTimeRaw)->toDateTimeString()
                    : $nowString,
                'exhausted' => false,
                'advanced' => false,
                'exhausted_at' => null,
            ];
        }

        if (! $sysTimeRaw) {
            return [
                'quarter' => min($quarter, $maxQuarters),
                'timer_remaining' => $remaining,
                'sys_time' => $nowString,
                'exhausted' => false,
                'advanced' => false,
                'exhausted_at' => null,
            ];
        }

        $anchor = Carbon::parse($sysTimeRaw);
        $elapsed = max(0, $now->getTimestamp() - $anchor->getTimestamp());
        $remainingAfter = $remaining - $elapsed;
        $advanced = $elapsed > 0;
        $exhausted = false;
        $exhaustedAt = null;

        while ($remainingAfter <= 0) {
            if ($quarter >= $maxQuarters) {
                // remainingAfter is <= 0 seconds past (or at) regulation end.
                $exhaustedAt = $now->copy()->addSeconds((int) $remainingAfter)->toDateTimeString();
                $remainingAfter = 0;
                $exhausted = true;
                break;
            }

            $overflow = abs($remainingAfter);
            $quarter++;
            $remainingAfter = $quarterLengthSeconds - $overflow;
            $advanced = true;
        }

        return [
            'quarter' => min($quarter, $maxQuarters),
            'timer_remaining' => (int) max(0, $remainingAfter),
            'sys_time' => $nowString,
            'exhausted' => $exhausted,
            'advanced' => $advanced,
            'exhausted_at' => $exhaustedAt,
        ];
    }

    /**
     * @param  array{quarter?: mixed, timer_remaining?: mixed, sys_time?: mixed, action?: mixed}  $row
     * @return array{quarter: int, timer_remaining: int, sys_time: string, exhausted: bool, advanced: bool, exhausted_at: ?string}
     */
    public function resolveForLeague(array $row, ?League $league, ?Carbon $now = null): array
    {
        return $this->resolve(
            $row,
            $this->quarterLengthSeconds($league),
            $this->maxQuarters($league),
            $now
        );
    }
}
