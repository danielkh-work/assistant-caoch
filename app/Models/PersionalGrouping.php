<?php

namespace App\Models;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersionalGrouping extends Model
{
    use HasFactory;

    protected $table = 'personal_groupings';
    protected $appends = ['players_data','practice_players_data'];
    protected $fillable = [
        'game_id',
        'league_id',
        'team_id',
        'group_name',
        'type',
        'players',
        'practice_players',
        'group_level',
        'status',
        'roster_repair_player_ids',
    ];

    protected $casts = [
        'players' => 'array',
        'practice_players' => 'array',
        'roster_repair_player_ids' => 'array',
    ];

    public function getPlayersDataAttribute()
    {
        if (! $this->players) {
            return collect();
        }

        $players = is_array($this->players) ? $this->players : json_decode($this->players, true);
        $ids = collect($players)->pluck('id');
        $teamPlayers = TeamPlayer::whereIn('id', $ids)->get()->keyBy('id');

        return collect($players)->map(function ($player) use ($teamPlayers) {
            if (is_int($player)) {
                $teamPlayer = $teamPlayers->get($player);
                if (! $teamPlayer) {
                    return null;
                }

                return [
                    'id' => $teamPlayer->id,
                    'name' => $teamPlayer->name,
                    'rpp' => $teamPlayer->rpp ?? 0,
                    'type' => $teamPlayer->type ?? 0,
                    'selected_position' => null,
                ];
            }

            if (is_array($player)) {
                $teamPlayer = $teamPlayers->get($player['id'] ?? null);
                if (! $teamPlayer) {
                    return null;
                }

                return [
                    'id' => $teamPlayer->id,
                    'name' => $teamPlayer->name,
                    'rpp' => $teamPlayer->rpp ?? 0,
                    'type' => $teamPlayer->type ?? 0,
                    'selected_position' => $player['positions'] ?? null,
                ];
            }

            return null;
        })->filter()->values();
    }

    public function getPracticePlayersDataAttribute()
    {
        if (empty($this->practice_players)) {
            return collect();
        }

        $players = is_array($this->practice_players) ? $this->practice_players : json_decode($this->practice_players, true);
        $ids = collect($players)->pluck('id');
        $practicePlayers = PracticeTeamPlayer::whereIn('id', $ids)->get()->keyBy('id');

        return collect($players)->map(function ($player) use ($practicePlayers) {
            $practicePlayer = $practicePlayers->get($player['id']);
            if (! $practicePlayer) {
                return null;
            }

            return [
                'id' => $practicePlayer->id,
                'name' => $practicePlayer->name,
                'rpp' => $practicePlayer->rpp,
                'selected_position' => $player['positions'],
            ];
        })->filter()->values();
    }

    public function plays()
    {
        return $this->belongsToMany(
            Play::class,
            'personal_grouping_play',
            'personal_grouping_id',
            'play_id'
        );
    }

    public function defensivePlays()
    {
        return $this->belongsToMany(
            DefensivePlay::class,
            'defensive_play_personal_grouping',
            'personal_grouping_id',
            'defensive_play_id'
        );
    }

    /**
     * Team / practice player IDs currently on the match roster (Configure Players).
     *
     * @param  int  $gameType  configure game_type: 1 = regular, 2 = practice
     * @return array<int,int>
     */
    public static function matchRosterPlayerIdsForConfigure(int $teamId, int $matchId, int $gameType): array
    {
        $isPracticeConfigure = (int) $gameType === 2;

        $rows = ConfiguredPlayingTeamPlayer::query()
            ->where('team_id', $teamId)
            ->where('match_id', $matchId)
            ->where('game_type', $gameType)
            ->get();

        $ids = [];
        foreach ($rows as $row) {
            if ($isPracticeConfigure && $row->practice_player_id) {
                $ids[] = (int) $row->practice_player_id;
            }
            if (! $isPracticeConfigure && $row->player_id) {
                $ids[] = (int) $row->player_id;
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * How many normalized group members appear on the configure/match roster (same IDs as
     * {@see countGroupMembersOnMatchRosterFromPayload} / syncStatusesForConfigureLanding).
     *
     * @param  array<int, array<string, mixed>>  $normalized  e.g. normalizeGroupPlayers() output
     */
    public static function countNormalizedPlayersOnMatchRoster(
        array $normalized,
        int $teamId,
        int $matchId,
        int $gameType
    ): int {
        $rosterFlip = array_flip(self::matchRosterPlayerIdsForConfigure($teamId, $matchId, $gameType));
        if ($rosterFlip === []) {
            return 0;
        }
        $n = 0;
        foreach ($normalized as $p) {
            $id = (int) ($p['id'] ?? 0);
            if ($id > 0 && isset($rosterFlip[$id])) {
                $n++;
            }
        }

        return $n;
    }

    /**
     * Keep only roster-repair IDs that still appear on the match roster.
     *
     * @param  array<int,mixed>  $repairIds
     * @return array<int,int>
     */
    public static function pruneRosterRepairIdsAgainstMatchRoster(array $repairIds, int $teamId, int $matchId, int $gameType): array
    {
        $repairIds = array_values(array_unique(array_map('intval', $repairIds)));
        if ($repairIds === []) {
            return [];
        }

        $allowed = self::matchRosterPlayerIdsForConfigure($teamId, $matchId, $gameType);
        if ($allowed === []) {
            return [];
        }

        $flip = array_flip($allowed);

        return array_values(array_filter($repairIds, fn ($id) => isset($flip[(int) $id])));
    }

    /**
     * After Configure Players save (or for self-heal on fetch), drop stale repair IDs for all groups on this match.
     */
    public static function pruneAllStaleRepairsAfterConfigureSave(int $teamId, int $matchId, int $gameType): void
    {
        $isPractice = (int) $gameType === 2;
        $expectedGroupLevel = $isPractice ? 2 : 1;

        $groups = self::query()
            ->where('team_id', $teamId)
            ->where('game_id', $matchId)
            ->where('group_level', $expectedGroupLevel)
            ->get();

        foreach ($groups as $group) {
            $repair = $group->roster_repair_player_ids ?? [];
            if (! is_array($repair) || $repair === []) {
                continue;
            }
            $repair = array_values(array_unique(array_map('intval', $repair)));
            $pruned = self::pruneRosterRepairIdsAgainstMatchRoster($repair, $teamId, $matchId, $gameType);
            if ($pruned !== $repair) {
                $group->roster_repair_player_ids = $pruned === [] ? null : $pruned;
                $group->save();
            }
        }
    }

    /**
     * Practice match end: demote `active` groups whose on-roster member count is no longer 7/11/12.
     * Off-roster ("Not in the match") residue is ignored, matching {@see syncStatusesForConfigureLanding}.
     */
    public static function syncInvalidActivePracticeGroupStatusesForMatchEnd(int $matchId): void
    {
        $game = Game::query()->find($matchId);
        if ($game === null) {
            return;
        }

        $configureGameType = (int) ($game->type ?? 1) === 2 ? 2 : 1;
        if ($configureGameType !== 2) {
            return;
        }

        $groups = self::query()
            ->where('game_id', $matchId)
            ->where('group_level', 2)
            ->get();

        $rosterFlipByTeam = [];

        foreach ($groups as $group) {
            if (strtolower((string) ($group->status ?? '')) !== 'active') {
                continue;
            }

            $teamId = (int) $group->team_id;
            if (! isset($rosterFlipByTeam[$teamId])) {
                $rosterFlipByTeam[$teamId] = array_flip(
                    self::matchRosterPlayerIdsForConfigure($teamId, $matchId, $configureGameType)
                );
            }

            $n = self::countGroupMembersOnMatchRosterFromPayload($group, true, $rosterFlipByTeam[$teamId]);

            if (! self::practiceGroupMemberCountIsValid($n)) {
                $group->status = 'inactive';
                $group->save();
            }
        }
    }

    /** @return array<int,int> Sizes that may remain or be set `active` for practice personal groups. */
    public static function practiceGroupAllowedMemberCounts(): array
    {
        return [7, 11, 12];
    }

    /** Practice personal groups: only these sizes may remain or be set `active`. */
    public static function practiceGroupActiveMemberCountIsValid(int $count): bool
    {
        return in_array($count, self::practiceGroupAllowedMemberCounts(), true);
    }

    private static function practiceGroupMemberCountIsValid(int $count): bool
    {
        return self::practiceGroupActiveMemberCountIsValid($count);
    }

    /**
     * Members saved on the group who still appear on the configure roster (coach-vue countGroupMembersOnMatchRoster).
     *
     * @param  array<int,true>  $rosterFlip
     */
    private static function countGroupMembersOnMatchRosterFromPayload(
        PersionalGrouping $group,
        bool $isPractice,
        array $rosterFlip
    ): int {
        $raw = $isPractice ? $group->practice_players : $group->players;
        $memberIds = self::collectIdsFromGroupedPlayersPayload($raw);
        $n = 0;
        foreach ($memberIds as $mid) {
            if (isset($rosterFlip[(int) $mid])) {
                $n++;
            }
        }

        return $n;
    }

    /**
     * Run when opening Configure → Players Grouping: prune repairs, then demote invalid `active` groups to `inactive`.
     * Does not modify `draft`. Idempotent.
     *
     * @return int Number of groups whose status was changed from active to inactive
     */
    public static function syncStatusesForConfigureLanding(
        int $teamId,
        int $matchId,
        int $gameType,
        int $leagueNonPracticePlayerLimit
    ): int {
        self::pruneAllStaleRepairsAfterConfigureSave($teamId, $matchId, $gameType);

        $isPractice = (int) $gameType === 2;
        $expectedGroupLevel = $isPractice ? 2 : 1;

        $rosterIds = self::matchRosterPlayerIdsForConfigure($teamId, $matchId, $gameType);
        $rosterFlip = array_flip($rosterIds);

        $lim = $leagueNonPracticePlayerLimit > 0 ? $leagueNonPracticePlayerLimit : 12;

        $groups = self::query()
            ->where('team_id', $teamId)
            ->where('game_id', $matchId)
            ->where('group_level', $expectedGroupLevel)
            ->get();

        $updated = 0;

        foreach ($groups as $group) {
            $stored = strtolower((string) ($group->status ?? 'active'));
            if ($stored === 'draft') {
                continue;
            }

            if ($stored !== 'active') {
                continue;
            }

            $repair = array_values(array_filter(array_map('intval', $group->roster_repair_player_ids ?? [])));
            $repair = self::pruneRosterRepairIdsAgainstMatchRoster($repair, $teamId, $matchId, $gameType);

            $n = self::countGroupMembersOnMatchRosterFromPayload($group, $isPractice, $rosterFlip);

            $shouldDemote = false;
            if ($repair !== []) {
                $shouldDemote = true;
            } elseif ($isPractice) {
                if (! self::practiceGroupActiveMemberCountIsValid($n)) {
                    $shouldDemote = true;
                }
            } elseif ($n !== $lim) {
                $shouldDemote = true;
            }

            if ($shouldDemote) {
                $group->status = 'inactive';
                $group->save();
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Match end: delete configure-player rows whose id is not in any group on that team
     * (active or inactive). `special` slot rows are always preserved.
     *
     * Group `status` is ignored on purpose — a group that became inactive (e.g. invalid member
     * count after a roster edit) still represents a coach intent worth keeping its players for.
     * Status is corrected separately by {@see syncInvalidActivePracticeGroupStatusesForMatchEnd}.
     */
    public static function pruneMatchConfigurePlayersNotInAnyGroup(int $matchId): void
    {
        $game = Game::query()->find($matchId);
        if ($game === null) {
            return;
        }

        $configureGameType = (int) ($game->type ?? 1) === 2 ? 2 : 1;
        $isPracticeConfigure = $configureGameType === 2;
        $expectedGroupLevel = $isPracticeConfigure ? 2 : 1;

        foreach (
            [
                ['team_id' => (int) $game->my_team_id, 'team_type' => 1],
                ['team_id' => (int) $game->oponent_team_id, 'team_type' => 2],
            ] as $side
        ) {
            if ($side['team_id'] <= 0) {
                continue;
            }

            $idsBySide = self::groupedPlayerIdsByConfigureSideFromGroups(
                $side['team_id'],
                $matchId,
                $expectedGroupLevel,
                $isPracticeConfigure
            );

            self::deleteConfiguredRowsWithoutMatchingGroupMembership(
                $side['team_id'],
                $matchId,
                $configureGameType,
                $side['team_type'],
                $idsBySide['offensive'],
                $idsBySide['defensive']
            );
        }
    }

    /**
     * @return array{offensive: array<int,int>, defensive: array<int,int>}
     */
    private static function groupedPlayerIdsByConfigureSideFromGroups(
        int $teamId,
        int $gameId,
        int $expectedGroupLevel,
        bool $usePracticePlayersPayload
    ): array {
        $offensiveFlip = [];
        $defensiveFlip = [];

        $groups = self::query()
            ->where('team_id', $teamId)
            ->where('game_id', $gameId)
            ->where('group_level', $expectedGroupLevel)
            ->get();

        foreach ($groups as $group) {
            $raw = $usePracticePlayersPayload ? $group->practice_players : $group->players;
            $memberIds = self::collectIdsFromGroupedPlayersPayload($raw);
            if ($memberIds === []) {
                continue;
            }

            $isOff = self::isOffensivePersonalGroupTypeLabel($group->type);
            foreach ($memberIds as $mid) {
                if ($mid <= 0) {
                    continue;
                }
                if ($isOff) {
                    $offensiveFlip[(int) $mid] = true;
                } else {
                    $defensiveFlip[(int) $mid] = true;
                }
            }
        }

        return [
            'offensive' => array_map('intval', array_keys($offensiveFlip)),
            'defensive' => array_map('intval', array_keys($defensiveFlip)),
        ];
    }

    /** @param  array<mixed>|string|null  $payload */
    private static function collectIdsFromGroupedPlayersPayload($payload): array
    {
        if ($payload === null || $payload === '') {
            return [];
        }
        if (! is_array($payload)) {
            $decoded = json_decode((string) $payload, true);
            if (! is_array($decoded)) {
                return [];
            }
            $payload = $decoded;
        }
        if ($payload === []) {
            return [];
        }

        $ids = [];
        foreach ($payload as $entry) {
            if (is_int($entry) || is_float($entry)) {
                $ids[] = (int) $entry;
            } elseif (is_string($entry) && ctype_digit($entry)) {
                $ids[] = (int) $entry;
            } elseif (is_array($entry) && isset($entry['id'])) {
                $ids[] = (int) $entry['id'];
            }
        }

        return array_values(array_unique(array_filter($ids, fn ($id) => (int) $id > 0)));
    }

    /** Same rules as coach-vue `isOffensiveGroupType` */
    private static function isOffensivePersonalGroupTypeLabel($type): bool
    {
        $t = strtolower((string) ($type ?? ''));
        if ($t === '' || str_contains($t, 'special')) {
            return false;
        }
        if ($t === 'offense' || $t === 'offensive') {
            return true;
        }
        if ($t === 'defense' || $t === 'defensive') {
            return false;
        }

        return str_contains($t, 'offen');
    }

    /**
     * Drop configure rows whose player id is not in the keep set (offensive ∪ defensive grouped ids).
     * Slot-type-agnostic because shuffle/bench paths insert rows with `type = NULL`. `special` preserved.
     *
     * @param  array<int,int>  $offensiveGroupedIds
     * @param  array<int,int>  $defensiveGroupedIds
     */
    private static function deleteConfiguredRowsWithoutMatchingGroupMembership(
        int $teamId,
        int $matchId,
        int $configureGameType,
        int $teamType,
        array $offensiveGroupedIds,
        array $defensiveGroupedIds
    ): void {
        $keep = [];
        foreach ([$offensiveGroupedIds, $defensiveGroupedIds] as $idList) {
            foreach ($idList as $id) {
                $n = (int) $id;
                if ($n > 0) {
                    $keep[$n] = true;
                }
            }
        }
        $isPracticeRow = ((int) $configureGameType) === 2;

        ConfiguredPlayingTeamPlayer::query()
            ->where('match_id', $matchId)
            ->where('team_id', $teamId)
            ->where('team_type', $teamType)
            ->get()
            ->each(function ($row) use ($keep, $isPracticeRow): void {
                $slotType = strtolower((string) ($row->type ?? ''));
                if ($slotType === 'special') {
                    return;
                }
                $pid = $isPracticeRow
                    ? (int) ($row->practice_player_id ?? 0)
                    : (int) ($row->player_id ?? 0);
                if ($pid <= 0) {
                    return;
                }
                if (! isset($keep[$pid])) {
                    $row->delete();
                }
            });
    }

}
