<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ConfiguredPlaySort
{
    public const MAX_CLAUSES = 5;

    public const KEY_PLAY_SUCCESS_RATE = 'play_success_rate';

    public const KEY_PRACTICE_SUCCESS_RATE = 'practice_success_rate';

    public const KEY_RAIN_SUCCESS_RATE = 'rain_success_rate';

    public const KEY_SNOW_SUCCESS_RATE = 'snow_success_rate';

    public const KEY_TOTAL_SCORE = 'total_score';

    public const ALLOWED_KEYS = [
        self::KEY_PLAY_SUCCESS_RATE,
        self::KEY_PRACTICE_SUCCESS_RATE,
        self::KEY_RAIN_SUCCESS_RATE,
        self::KEY_SNOW_SUCCESS_RATE,
        self::KEY_TOTAL_SCORE,
    ];

    private const RATE_SQL = [
        self::KEY_PLAY_SUCCESS_RATE => 'COALESCE(win_result / NULLIF(total_count, 0), 0)',
        self::KEY_PRACTICE_SUCCESS_RATE => 'COALESCE(practice_win_result / NULLIF(total_practice_count, 0), 0)',
        self::KEY_RAIN_SUCCESS_RATE => 'COALESCE(win_result_rain / NULLIF(total_rain, 0), 0)',
        self::KEY_SNOW_SUCCESS_RATE => 'COALESCE(win_result_snow / NULLIF(total_snow, 0), 0)',
    ];

    private const RATE_NUMERATOR = [
        self::KEY_PLAY_SUCCESS_RATE => 'win_result',
        self::KEY_PRACTICE_SUCCESS_RATE => 'practice_win_result',
        self::KEY_RAIN_SUCCESS_RATE => 'win_result_rain',
        self::KEY_SNOW_SUCCESS_RATE => 'win_result_snow',
    ];

    private const RATE_DENOMINATOR = [
        self::KEY_PLAY_SUCCESS_RATE => 'total_count',
        self::KEY_PRACTICE_SUCCESS_RATE => 'total_practice_count',
        self::KEY_RAIN_SUCCESS_RATE => 'total_rain',
        self::KEY_SNOW_SUCCESS_RATE => 'total_snow',
    ];

    /**
     * @return list<array{key: string, dir: string}>
     */
    public function parseSort(?string $sort): array
    {
        if ($sort === null || trim($sort) === '') {
            return [];
        }

        $clauses = array_values(array_filter(array_map('trim', explode(',', $sort))));

        if (count($clauses) > self::MAX_CLAUSES) {
            throw ValidationException::withMessages([
                'sort' => ['A maximum of ' . self::MAX_CLAUSES . ' sort clauses is allowed.'],
            ]);
        }

        $parsed = [];

        foreach ($clauses as $clause) {
            if (!preg_match('/^([a-z_]+):(asc|desc)$/i', $clause, $matches)) {
                throw ValidationException::withMessages([
                    'sort' => ["Invalid sort clause \"{$clause}\". Expected format: field:asc or field:desc."],
                ]);
            }

            $key = strtolower($matches[1]);
            $dir = strtolower($matches[2]);

            if (!in_array($key, self::ALLOWED_KEYS, true)) {
                throw ValidationException::withMessages([
                    'sort' => ["Unknown sort field \"{$key}\". Allowed: " . implode(', ', self::ALLOWED_KEYS) . '.'],
                ]);
            }

            $parsed[] = ['key' => $key, 'dir' => $dir];
        }

        return $parsed;
    }

    public function requiresTotalScore(array $sorts): bool
    {
        foreach ($sorts as $sort) {
            if ($sort['key'] === self::KEY_TOTAL_SCORE) {
                return true;
            }
        }

        return false;
    }

    public function applySqlSorts(Builder $query, array $sorts, string $table): void
    {
        foreach ($sorts as $sort) {
            if ($sort['key'] === self::KEY_TOTAL_SCORE) {
                continue;
            }

            $direction = strtoupper($sort['dir']) === 'DESC' ? 'DESC' : 'ASC';
            $query->orderByRaw(self::RATE_SQL[$sort['key']] . ' ' . $direction);
        }

        $query->orderBy("{$table}.id", 'asc');
    }

    public function applyDefaultSqlSort(Builder $query, string $table): void
    {
        $query->orderByDesc('win_result')
            ->orderBy("{$table}.id", 'asc');
    }

    /**
     * @param Collection<int, object> $plays
     * @return Collection<int, object>
     */
    public function applyCollectionSorts(Collection $plays, array $sorts): Collection
    {
        if ($sorts === []) {
            return $plays->sortByDesc('win_result')->sortBy('id')->values();
        }

        return $plays->sort(function ($left, $right) use ($sorts) {
            foreach ($sorts as $sort) {
                $leftValue = $this->sortValue($left, $sort['key']);
                $rightValue = $this->sortValue($right, $sort['key']);
                $cmp = $leftValue <=> $rightValue;

                if ($cmp !== 0) {
                    return $sort['dir'] === 'desc' ? -$cmp : $cmp;
                }
            }

            return ($left->id ?? 0) <=> ($right->id ?? 0);
        })->values();
    }

    public function sortValue(object $play, string $key): float
    {
        if ($key === self::KEY_TOTAL_SCORE) {
            return (float) ($play->total_score ?? 0);
        }

        return $this->rateValue($play, $key);
    }

    public function rateValue(object $play, string $key): float
    {
        $numerator = (float) ($play->{self::RATE_NUMERATOR[$key]} ?? 0);
        $denominator = (float) ($play->{self::RATE_DENOMINATOR[$key]} ?? 0);

        if ($denominator <= 0) {
            return 0.0;
        }

        return $numerator / $denominator;
    }
}
