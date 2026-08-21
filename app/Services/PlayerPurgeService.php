<?php

namespace App\Services;

use App\Models\BenchPlayer;
use App\Models\ConfiguredPlayingTeamPlayer;
use App\Models\DefensivePlayPersonal;
use App\Models\OpponentPackagePlayer;
use App\Models\PersionalGrouping;
use App\Models\Player;
use App\Models\PlayerPosition;
use App\Models\PlayGameLog;
use App\Models\PracticeTeamPlayer;
use App\Models\PracticeTeamPlayerPosition;
use App\Models\TeamGroup;
use App\Models\TeamPlayer;
use App\Models\TeamPlayerPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlayerPurgeService
{
    /** Case-insensitive substring matches on player name. */
    private const NAME_KEYWORD_PATTERNS = ['%test%', '%testing%', '%demo%'];

    /** Whole-word match; avoids names like "Devon". */
    private const NAME_DEV_WORD_REGEX = '(^|[^a-z])dev([^a-z]|$)';

    /** Exact junk names (case-insensitive, trimmed). Update this list only. */
    private const EXACT_JUNK_NAMES = [
        '123.',
        '?',
        '56732',
        '1weqfr23',
        'asasaa',
        'asd',
        'sersdf',
        'rewr',
    ];

    /** @return list<string> */
    public static function exactJunkNames(): array
    {
        return self::EXACT_JUNK_NAMES;
    }

    /** @return Builder<Player> */
    public static function testPlayerQuery(): Builder
    {
        return Player::query()->where(function (Builder $query) {
            foreach (self::NAME_KEYWORD_PATTERNS as $pattern) {
                $query->orWhereRaw('LOWER(name) LIKE ?', [$pattern]);
            }

            $query->orWhereRaw('LOWER(name) REGEXP ?', [self::NAME_DEV_WORD_REGEX])
                ->orWhereIn(DB::raw('LOWER(TRIM(name))'), self::EXACT_JUNK_NAMES);
        });
    }

    /**
     * @param  array<int,int>  $playerIds
     * @return array<string, int|array<int>>
     */
    public function purgeByPlayerIds(array $playerIds, bool $dryRun = false): array
    {
        $playerIds = array_values(array_unique(array_map('intval', array_filter($playerIds, fn ($id) => (int) $id > 0))));

        if ($playerIds === []) {
            return ['player_ids' => [], 'dry_run' => $dryRun];
        }

        $playerIds = Player::withTrashed()
            ->whereIn('id', $playerIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($playerIds === []) {
            return ['player_ids' => [], 'dry_run' => $dryRun];
        }

        $teamPlayerIds = TeamPlayer::withTrashed()
            ->whereIn('player_id', $playerIds)
            ->pluck('id');

        $practicePlayerIds = $this->resolvePracticePlayerIds($teamPlayerIds, $playerIds);

        $stats = [
            'dry_run' => $dryRun,
            'player_ids' => $playerIds,
            'team_player_ids' => $teamPlayerIds->all(),
            'practice_player_ids' => $practicePlayerIds->all(),
            'relational' => [],
            'json' => [],
        ];

        if ($dryRun) {
            $stats['relational'] = $this->countRelationalRows($playerIds, $teamPlayerIds, $practicePlayerIds);
            $stats['json'] = $this->countJsonRows($playerIds, $teamPlayerIds, $practicePlayerIds);

            return $stats;
        }

        DB::transaction(function () use ($playerIds, $teamPlayerIds, $practicePlayerIds, &$stats) {
            $teamPlayersByTeam = TeamPlayer::withTrashed()
                ->whereIn('player_id', $playerIds)
                ->get()
                ->groupBy('team_id');

            $stats['json'] = $this->scrubJsonReferences($playerIds, $teamPlayerIds, $practicePlayerIds, $teamPlayersByTeam);
            $stats['relational'] = $this->softDeleteRelationalRows($playerIds, $teamPlayerIds, $practicePlayerIds);
            $stats['players_soft_deleted'] = Player::withTrashed()
                ->whereIn('id', $playerIds)
                ->whereNull('deleted_at')
                ->delete();
        });

        return $stats;
    }

    /** @param  Collection<int,int>  $teamPlayerIds */
    /** @param  array<int,int>  $playerIds */
    private function resolvePracticePlayerIds(Collection $teamPlayerIds, array $playerIds): Collection
    {
        return PracticeTeamPlayer::withTrashed()
            ->where(function (Builder $query) use ($teamPlayerIds, $playerIds) {
                if ($teamPlayerIds->isNotEmpty()) {
                    $query->whereIn('player_id', $teamPlayerIds);
                }
                if ($playerIds !== []) {
                    $query->orWhereIn('player_id', $playerIds);
                }
            })
            ->pluck('id')
            ->unique()
            ->values();
    }

    /**
     * @param  array<int,int>  $playerIds
     * @param  Collection<int,int>  $teamPlayerIds
     * @param  Collection<int,int>  $practicePlayerIds
     * @return array<string, int>
     */
    private function countRelationalRows(array $playerIds, Collection $teamPlayerIds, Collection $practicePlayerIds): array
    {
        return [
            'players' => Player::withTrashed()->whereIn('id', $playerIds)->whereNull('deleted_at')->count(),
            'team_players' => $teamPlayerIds->isEmpty() ? 0 : TeamPlayer::withTrashed()->whereIn('id', $teamPlayerIds)->whereNull('deleted_at')->count(),
            'practice_team_players' => $practicePlayerIds->isEmpty() ? 0 : PracticeTeamPlayer::withTrashed()->whereIn('id', $practicePlayerIds)->whereNull('deleted_at')->count(),
            'player_positions' => PlayerPosition::query()->whereIn('player_id', $playerIds)->count(),
            'team_player_positions' => $teamPlayerIds->isEmpty() ? 0 : TeamPlayerPosition::query()->whereIn('teamplayer_id', $teamPlayerIds)->count(),
            'practice_team_player_positions' => $practicePlayerIds->isEmpty() ? 0 : PracticeTeamPlayerPosition::query()->whereIn('practice_team_player_id', $practicePlayerIds)->count(),
            'configured_playing_team_players' => $this->countConfiguredPlayingRows($teamPlayerIds, $practicePlayerIds),
            'offense_defense_players' => $this->countOffenseDefenseRows($teamPlayerIds, $practicePlayerIds),
            'opponent_package_player' => $this->countOpponentPackageRows($teamPlayerIds, $practicePlayerIds),
            'defensive_play_personals' => $teamPlayerIds->isEmpty() ? 0 : DefensivePlayPersonal::query()->whereIn('teamplayer_id', $teamPlayerIds)->count(),
            'play_game_logs_scalar' => $teamPlayerIds->isEmpty() ? 0 : DB::table('play_game_logs')->whereIn('player_id', $teamPlayerIds)->count(),
            'roleables' => DB::table('roleables')->where('roleable_type', 'like', '%Player%')->whereIn('roleable_id', $playerIds)->count(),
        ];
    }

    /**
     * @param  array<int,int>  $playerIds
     * @param  Collection<int,int>  $teamPlayerIds
     * @param  Collection<int,int>  $practicePlayerIds
     * @return array<string, int>
     */
    private function countJsonRows(array $playerIds, Collection $teamPlayerIds, Collection $practicePlayerIds): array
    {
        return [
            'personal_groupings' => $this->countGroupingJsonRows(PersionalGrouping::query(), $playerIds, $teamPlayerIds, $practicePlayerIds),
            'team_groups' => $this->countGroupingJsonRows(TeamGroup::query(), $playerIds, $teamPlayerIds, $practicePlayerIds),
            'play_game_logs' => PlayGameLog::query()->get()->filter(fn (PlayGameLog $log) => $this->logReferencesRemovedIds($log, $playerIds, $teamPlayerIds, $practicePlayerIds))->count(),
        ];
    }

    /**
     * @param  array<int,int>  $playerIds
     * @param  Collection<int,int>  $teamPlayerIds
     * @param  Collection<int,int>  $practicePlayerIds
     * @return array<string, int>
     */
    private function softDeleteRelationalRows(array $playerIds, Collection $teamPlayerIds, Collection $practicePlayerIds): array
    {
        $counts = [];

        if ($teamPlayerIds->isNotEmpty()) {
            $counts['team_player_positions'] = TeamPlayerPosition::withTrashed()
                ->whereIn('teamplayer_id', $teamPlayerIds)
                ->whereNull('deleted_at')
                ->delete();
            $counts['defensive_play_personals'] = DefensivePlayPersonal::withTrashed()
                ->whereIn('teamplayer_id', $teamPlayerIds)
                ->whereNull('deleted_at')
                ->delete();
        }

        $counts['configured_playing_team_players'] = $this->softDeleteConfiguredPlayingRows($teamPlayerIds, $practicePlayerIds);
        $counts['offense_defense_players'] = $this->softDeleteOffenseDefenseRows($teamPlayerIds, $practicePlayerIds);
        $counts['opponent_package_player'] = $this->softDeleteOpponentPackageRows($teamPlayerIds, $practicePlayerIds);

        if ($practicePlayerIds->isNotEmpty()) {
            $counts['practice_team_player_positions'] = PracticeTeamPlayerPosition::withTrashed()
                ->whereIn('practice_team_player_id', $practicePlayerIds)
                ->whereNull('deleted_at')
                ->delete();
            $counts['practice_team_players'] = PracticeTeamPlayer::withTrashed()
                ->whereIn('id', $practicePlayerIds)
                ->whereNull('deleted_at')
                ->delete();
        }

        if ($teamPlayerIds->isNotEmpty()) {
            $counts['play_game_logs_scalar'] = DB::table('play_game_logs')
                ->whereIn('player_id', $teamPlayerIds)
                ->update(['player_id' => null]);
            $counts['team_players'] = TeamPlayer::withTrashed()
                ->whereIn('id', $teamPlayerIds)
                ->whereNull('deleted_at')
                ->delete();
        }

        $counts['player_positions'] = PlayerPosition::withTrashed()
            ->whereIn('player_id', $playerIds)
            ->whereNull('deleted_at')
            ->delete();
        $counts['roleables'] = DB::table('roleables')
            ->where('roleable_type', 'like', '%Player%')
            ->whereIn('roleable_id', $playerIds)
            ->delete();

        return $counts;
    }

    /**
     * @param  array<int,int>  $playerIds
     * @param  Collection<int,int>  $teamPlayerIds
     * @param  Collection<int,int>  $practicePlayerIds
     * @param  Collection<int|string, Collection<int, TeamPlayer>>  $teamPlayersByTeam
     * @return array<string, int>
     */
    private function scrubJsonReferences(
        array $playerIds,
        Collection $teamPlayerIds,
        Collection $practicePlayerIds,
        Collection $teamPlayersByTeam,
    ): array {
        $stats = [
            'personal_groupings' => 0,
            'team_groups' => 0,
            'play_game_logs' => 0,
        ];

        foreach ($teamPlayersByTeam as $teamId => $rows) {
            $removedTeamIds = $rows->pluck('id')->map(fn ($id) => (int) $id)->all();
            $removedPracticeIds = PracticeTeamPlayer::query()
                ->where('team_id', (int) $teamId)
                ->where(function (Builder $query) use ($removedTeamIds, $playerIds) {
                    if ($removedTeamIds !== []) {
                        $query->whereIn('player_id', $removedTeamIds);
                    }
                    if ($playerIds !== []) {
                        $query->orWhereIn('player_id', $playerIds);
                    }
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $stats['personal_groupings'] += PersionalGrouping::removeMemberIdsFromAllTeamGroups(
                (int) $teamId,
                $removedPracticeIds,
                $removedTeamIds,
            );
        }

        $teamFlip = array_flip($teamPlayerIds->map(fn ($id) => (int) $id)->all());
        $practiceFlip = array_flip($practicePlayerIds->map(fn ($id) => (int) $id)->all());
        $playerFlip = array_flip($playerIds);

        TeamGroup::query()->get()->each(function (TeamGroup $group) use ($teamFlip, $practiceFlip, &$stats) {
            $changed = false;

            foreach (['players', 'practice_players'] as $column) {
                $filtered = $this->filterGroupedPlayersPayloadRemovingIds($group->{$column}, $teamFlip, $practiceFlip);
                if ($this->payloadChanged($group->{$column}, $filtered)) {
                    $group->{$column} = $filtered === [] ? null : $filtered;
                    $changed = true;
                }
            }

            if ($changed) {
                $group->save();
                $stats['team_groups']++;
            }
        });

        PlayGameLog::query()->get()->each(function (PlayGameLog $log) use ($playerFlip, $teamFlip, $practiceFlip, &$stats) {
            $changed = false;

            foreach (['players', 'players_in', 'players_out'] as $column) {
                $filtered = $this->filterLogPlayersPayloadRemovingIds($log->{$column}, $teamFlip, $practiceFlip, $playerFlip);
                if ($this->payloadChanged($log->{$column}, $filtered)) {
                    $log->{$column} = $filtered === [] ? null : $filtered;
                    $changed = true;
                }
            }

            $practiceRaw = $log->getAttributes()['practice_players'] ?? null;
            $practiceFiltered = $this->filterLogPlayersPayloadRemovingIds(
                $this->decodeJsonValue($practiceRaw),
                $teamFlip,
                $practiceFlip,
                $playerFlip,
            );
            if ($this->payloadChanged($this->decodeJsonValue($practiceRaw), $practiceFiltered)) {
                $log->practice_players = $practiceFiltered === [] ? null : json_encode($practiceFiltered);
                $changed = true;
            }

            if ($changed) {
                $log->save();
                $stats['play_game_logs']++;
            }
        });

        return $stats;
    }

    /** @param  Collection<int,int>  $teamPlayerIds */
    /** @param  Collection<int,int>  $practicePlayerIds */
    private function countConfiguredPlayingRows(Collection $teamPlayerIds, Collection $practicePlayerIds): int
    {
        return ConfiguredPlayingTeamPlayer::withTrashed()
            ->where(function ($query) use ($teamPlayerIds, $practicePlayerIds) {
                if ($teamPlayerIds->isNotEmpty()) {
                    $query->whereIn('player_id', $teamPlayerIds);
                }
                if ($practicePlayerIds->isNotEmpty()) {
                    $query->orWhereIn('practice_player_id', $practicePlayerIds);
                }
            })
            ->whereNull('deleted_at')
            ->count();
    }

    /** @param  Collection<int,int>  $teamPlayerIds */
    /** @param  Collection<int,int>  $practicePlayerIds */
    private function softDeleteConfiguredPlayingRows(Collection $teamPlayerIds, Collection $practicePlayerIds): int
    {
        if ($teamPlayerIds->isEmpty() && $practicePlayerIds->isEmpty()) {
            return 0;
        }

        return ConfiguredPlayingTeamPlayer::withTrashed()
            ->where(function ($query) use ($teamPlayerIds, $practicePlayerIds) {
                if ($teamPlayerIds->isNotEmpty()) {
                    $query->whereIn('player_id', $teamPlayerIds);
                }
                if ($practicePlayerIds->isNotEmpty()) {
                    $query->orWhereIn('practice_player_id', $practicePlayerIds);
                }
            })
            ->whereNull('deleted_at')
            ->delete();
    }

    /** @param  Collection<int,int>  $teamPlayerIds */
    /** @param  Collection<int,int>  $practicePlayerIds */
    private function countOffenseDefenseRows(Collection $teamPlayerIds, Collection $practicePlayerIds): int
    {
        return BenchPlayer::withTrashed()
            ->where(function ($query) use ($teamPlayerIds, $practicePlayerIds) {
                if ($teamPlayerIds->isNotEmpty()) {
                    $query->whereIn('player_id', $teamPlayerIds);
                }
                if ($practicePlayerIds->isNotEmpty()) {
                    $query->orWhereIn('practice_player_id', $practicePlayerIds);
                }
            })
            ->whereNull('deleted_at')
            ->count();
    }

    /** @param  Collection<int,int>  $teamPlayerIds */
    /** @param  Collection<int,int>  $practicePlayerIds */
    private function softDeleteOffenseDefenseRows(Collection $teamPlayerIds, Collection $practicePlayerIds): int
    {
        if ($teamPlayerIds->isEmpty() && $practicePlayerIds->isEmpty()) {
            return 0;
        }

        return BenchPlayer::withTrashed()
            ->where(function ($query) use ($teamPlayerIds, $practicePlayerIds) {
                if ($teamPlayerIds->isNotEmpty()) {
                    $query->whereIn('player_id', $teamPlayerIds);
                }
                if ($practicePlayerIds->isNotEmpty()) {
                    $query->orWhereIn('practice_player_id', $practicePlayerIds);
                }
            })
            ->whereNull('deleted_at')
            ->delete();
    }

    /** @param  Collection<int,int>  $teamPlayerIds */
    /** @param  Collection<int,int>  $practicePlayerIds */
    private function countOpponentPackageRows(Collection $teamPlayerIds, Collection $practicePlayerIds): int
    {
        return OpponentPackagePlayer::withTrashed()
            ->where(function ($query) use ($teamPlayerIds, $practicePlayerIds) {
                if ($teamPlayerIds->isNotEmpty()) {
                    $query->where(function ($q) use ($teamPlayerIds) {
                        $q->where('is_practice', 0)->whereIn('player_id', $teamPlayerIds);
                    });
                }
                if ($practicePlayerIds->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($practicePlayerIds) {
                        $q->where('is_practice', 1)->whereIn('player_id', $practicePlayerIds);
                    });
                }
            })
            ->whereNull('deleted_at')
            ->count();
    }

    /** @param  Collection<int,int>  $teamPlayerIds */
    /** @param  Collection<int,int>  $practicePlayerIds */
    private function softDeleteOpponentPackageRows(Collection $teamPlayerIds, Collection $practicePlayerIds): int
    {
        if ($teamPlayerIds->isEmpty() && $practicePlayerIds->isEmpty()) {
            return 0;
        }

        return OpponentPackagePlayer::withTrashed()
            ->where(function ($query) use ($teamPlayerIds, $practicePlayerIds) {
                if ($teamPlayerIds->isNotEmpty()) {
                    $query->where(function ($q) use ($teamPlayerIds) {
                        $q->where('is_practice', 0)->whereIn('player_id', $teamPlayerIds);
                    });
                }
                if ($practicePlayerIds->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($practicePlayerIds) {
                        $q->where('is_practice', 1)->whereIn('player_id', $practicePlayerIds);
                    });
                }
            })
            ->whereNull('deleted_at')
            ->delete();
    }

    /**
     * @param  array<int,int>  $playerIds
     * @param  Collection<int,int>  $teamPlayerIds
     * @param  Collection<int,int>  $practicePlayerIds
     */
    private function countGroupingJsonRows(Builder $query, array $playerIds, Collection $teamPlayerIds, Collection $practicePlayerIds): int
    {
        $teamFlip = array_flip($teamPlayerIds->map(fn ($id) => (int) $id)->all());
        $practiceFlip = array_flip($practicePlayerIds->map(fn ($id) => (int) $id)->all());
        $playerFlip = array_flip($playerIds);

        return $query->get()->filter(function ($group) use ($teamFlip, $practiceFlip, $playerFlip) {
            foreach (['players', 'practice_players'] as $column) {
                if ($this->payloadReferencesRemovedIds($group->{$column}, $teamFlip, $practiceFlip, $playerFlip)) {
                    return true;
                }
            }

            $repair = $this->decodeJsonValue($group->roster_repair_player_ids ?? null);
            if (is_array($repair)) {
                foreach ($repair as $id) {
                    if (isset($teamFlip[(int) $id]) || isset($practiceFlip[(int) $id])) {
                        return true;
                    }
                }
            }

            return false;
        })->count();
    }

    /**
     * @param  array<int,int>  $playerIds
     * @param  Collection<int,int>  $teamPlayerIds
     * @param  Collection<int,int>  $practicePlayerIds
     */
    private function logReferencesRemovedIds(PlayGameLog $log, array $playerIds, Collection $teamPlayerIds, Collection $practicePlayerIds): bool
    {
        $teamFlip = array_flip($teamPlayerIds->map(fn ($id) => (int) $id)->all());
        $practiceFlip = array_flip($practicePlayerIds->map(fn ($id) => (int) $id)->all());
        $playerFlip = array_flip($playerIds);

        if ($log->player_id !== null && isset($teamFlip[(int) $log->player_id])) {
            return true;
        }

        foreach (['players', 'players_in', 'players_out'] as $column) {
            if ($this->payloadReferencesRemovedIds($log->{$column}, $teamFlip, $practiceFlip, $playerFlip)) {
                return true;
            }
        }

        return $this->payloadReferencesRemovedIds(
            $this->decodeJsonValue($log->getAttributes()['practice_players'] ?? null),
            $teamFlip,
            $practiceFlip,
            $playerFlip,
        );
    }

    /** @param  array<int, int>  $teamFlip */
    /** @param  array<int, int>  $practiceFlip */
    /** @param  array<int, int>  $playerFlip */
    private function payloadReferencesRemovedIds(mixed $payload, array $teamFlip, array $practiceFlip, array $playerFlip): bool
    {
        $payload = $this->decodeJsonValue($payload);
        if (! is_array($payload)) {
            return false;
        }

        foreach ($payload as $entry) {
            if (is_int($entry) || is_float($entry)) {
                $id = (int) $entry;
                if (isset($teamFlip[$id]) || isset($practiceFlip[$id])) {
                    return true;
                }
            } elseif (is_string($entry) && ctype_digit($entry)) {
                $id = (int) $entry;
                if (isset($teamFlip[$id]) || isset($practiceFlip[$id])) {
                    return true;
                }
            } elseif (is_array($entry)) {
                if (isset($entry['id']) && (isset($teamFlip[(int) $entry['id']]) || isset($practiceFlip[(int) $entry['id']]))) {
                    return true;
                }
                if (isset($entry['player_id']) && isset($playerFlip[(int) $entry['player_id']])) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @param  array<int, int>  $teamFlip */
    /** @param  array<int, int>  $practiceFlip */
    private function filterGroupedPlayersPayloadRemovingIds(mixed $payload, array $teamFlip, array $practiceFlip): array
    {
        $removedFlip = $teamFlip + $practiceFlip;

        return $this->filterPayloadRemovingIds($payload, $removedFlip, false);
    }

    /** @param  array<int, int>  $teamFlip */
    /** @param  array<int, int>  $practiceFlip */
    /** @param  array<int, int>  $playerFlip */
    private function filterLogPlayersPayloadRemovingIds(mixed $payload, array $teamFlip, array $practiceFlip, array $playerFlip): array
    {
        $payload = $this->decodeJsonValue($payload);
        if (! is_array($payload)) {
            return [];
        }

        $out = [];
        foreach ($payload as $entry) {
            if (is_int($entry) || is_float($entry)) {
                $id = (int) $entry;
                if (isset($teamFlip[$id]) || isset($practiceFlip[$id])) {
                    continue;
                }
            } elseif (is_string($entry) && ctype_digit($entry)) {
                $id = (int) $entry;
                if (isset($teamFlip[$id]) || isset($practiceFlip[$id])) {
                    continue;
                }
            } elseif (is_array($entry)) {
                if (isset($entry['id']) && (isset($teamFlip[(int) $entry['id']]) || isset($practiceFlip[(int) $entry['id']]))) {
                    continue;
                }
                if (isset($entry['player_id']) && isset($playerFlip[(int) $entry['player_id']])) {
                    continue;
                }
            }

            $out[] = $entry;
        }

        return array_values($out);
    }

    /** @param  array<int, int>  $removedFlip */
    private function filterPayloadRemovingIds(mixed $payload, array $removedFlip, bool $matchPlayerId): array
    {
        $payload = $this->decodeJsonValue($payload);
        if (! is_array($payload)) {
            return [];
        }

        $out = [];
        foreach ($payload as $entry) {
            $remove = false;

            if (is_int($entry) || is_float($entry)) {
                $remove = isset($removedFlip[(int) $entry]);
            } elseif (is_string($entry) && ctype_digit($entry)) {
                $remove = isset($removedFlip[(int) $entry]);
            } elseif (is_array($entry)) {
                if (isset($entry['id'])) {
                    $remove = isset($removedFlip[(int) $entry['id']]);
                }
                if (! $remove && $matchPlayerId && isset($entry['player_id'])) {
                    $remove = isset($removedFlip[(int) $entry['player_id']]);
                }
            }

            if (! $remove) {
                $out[] = $entry;
            }
        }

        return array_values($out);
    }

    private function decodeJsonValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function payloadChanged(mixed $before, array $after): bool
    {
        $beforeNormalized = $this->decodeJsonValue($before) ?? [];
        $afterNormalized = $after === [] ? [] : $after;

        return json_encode($beforeNormalized) !== json_encode($afterNormalized);
    }
}
