<?php

namespace App\Models;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersionalGrouping extends Model
{
    use HasFactory;

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $group): void {
            $group->applyActiveStatusGuardOnSave();
        });
    }

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
     * Player ids on the configure roster for one team side before/after a save.
     *
     * @return array<int,int>
     */
    public static function configureRosterPlayerIdsForTeamSide(
        int $teamId,
        int $matchId,
        int $gameType,
        int $teamType
    ): array {
        $rows = ConfiguredPlayingTeamPlayer::query()
            ->where('team_id', $teamId)
            ->where('match_id', $matchId)
            ->where('game_type', $gameType)
            ->where('team_type', $teamType)
            ->get();

        return self::extractMatchRosterPlayerIdsFromConfigureRows($rows, $gameType, false);
    }

    /**
     * Team / practice player IDs currently on the match roster (Configure Players).
     *
     * @param  int  $gameType  configure game_type: 1 = regular, 2 = practice
     * @return array<int,int>
     */
    public static function matchRosterPlayerIdsForConfigure(int $teamId, int $matchId, int $gameType): array
    {
        return self::extractMatchRosterPlayerIdsFromConfigureRows(
            self::configuredPlayingTeamRowsForMatch($teamId, $matchId, $gameType),
            $gameType,
            false
        );
    }

    /**
     * Same IDs as coach-vue `playerMap` / `matchRosterPlayerIds` (configure rows with a resolvable player).
     *
     * @return array<int,int>
     */
    public static function matchRosterPlayerIdsForConfigureResolved(int $teamId, int $matchId, int $gameType): array
    {
        return self::extractMatchRosterPlayerIdsFromConfigureRows(
            self::configuredPlayingTeamRowsForMatch($teamId, $matchId, $gameType, true),
            $gameType,
            true
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, ConfiguredPlayingTeamPlayer>
     */
    private static function configuredPlayingTeamRowsForMatch(
        int $teamId,
        int $matchId,
        int $gameType,
        bool $eagerLoadRelations = false
    ) {
        $query = ConfiguredPlayingTeamPlayer::query()
            ->where('team_id', $teamId)
            ->where('match_id', $matchId)
            ->where('game_type', $gameType);

        if ($eagerLoadRelations) {
            $isPracticeConfigure = (int) $gameType === 2;
            $query->with(
                $isPracticeConfigure
                    ? ['practice_player']
                    : ['player.player']
            );
        }

        return $query->get();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ConfiguredPlayingTeamPlayer>  $rows
     * @return array<int,int>
     */
    private static function extractMatchRosterPlayerIdsFromConfigureRows($rows, int $gameType, bool $requireResolvedRelation): array
    {
        $isPracticeConfigure = (int) $gameType === 2;
        $ids = [];

        foreach ($rows as $row) {
            if ($isPracticeConfigure) {
                if (! $row->practice_player_id) {
                    continue;
                }
                if ($requireResolvedRelation && ! $row->practice_player) {
                    continue;
                }
                $ids[] = (int) $row->practice_player_id;
            } else {
                if (! $row->player_id) {
                    continue;
                }
                if ($requireResolvedRelation && (! $row->player || ! $row->player->player)) {
                    continue;
                }
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
     * After Configure Players save: prune repairs, demote active groups that lost roster members, then
     * demote any remaining active groups with invalid on-roster counts. Does not modify `draft`. Idempotent.
     *
     * @param  array<int,mixed>  $removedRosterPlayerIds  Player ids removed from the match configure roster
     * @return int Number of groups whose status was changed from active to inactive
     */
    public static function syncAfterConfigureRosterSave(
        int $teamId,
        int $matchId,
        int $gameType,
        array $removedRosterPlayerIds = []
    ): int {
        self::pruneAllStaleRepairsAfterConfigureSave($teamId, $matchId, $gameType);

        $updated = self::deactivateActiveGroupsForRemovedRosterPlayerIds(
            $teamId,
            $matchId,
            $gameType,
            $removedRosterPlayerIds
        );

        $updated += self::syncStatusesForConfigureLanding($teamId, $matchId, $gameType);

        return $updated;
    }

    /**
     * Demote `active` groups that still list players removed from the configure/match roster and record
     * roster-repair ids (same behaviour as coach-vue `deactivateGroupsForRemovedPlayers`).
     *
     * @param  array<int,mixed>  $removedRosterPlayerIds
     * @return int Number of groups demoted
     */
    public static function deactivateActiveGroupsForRemovedRosterPlayerIds(
        int $teamId,
        int $matchId,
        int $gameType,
        array $removedRosterPlayerIds
    ): int {
        $removedRosterPlayerIds = array_values(array_unique(array_map('intval', $removedRosterPlayerIds)));
        if ($removedRosterPlayerIds === []) {
            return 0;
        }

        $removedFlip = array_flip($removedRosterPlayerIds);
        $isPractice = (int) $gameType === 2;
        $expectedGroupLevel = $isPractice ? 2 : 1;

        $groups = self::query()
            ->where('team_id', $teamId)
            ->where('game_id', $matchId)
            ->where('group_level', $expectedGroupLevel)
            ->where('status', 'active')
            ->get();

        $updated = 0;

        foreach ($groups as $group) {
            $raw = $isPractice ? $group->practice_players : $group->players;
            $memberIds = self::collectIdsFromGroupedPlayersPayload($raw);
            $affectedRemoved = array_values(array_filter(
                $memberIds,
                fn ($id) => isset($removedFlip[(int) $id])
            ));

            if ($affectedRemoved === []) {
                continue;
            }

            $repair = array_values(array_unique(array_merge(
                array_map('intval', $group->roster_repair_player_ids ?? []),
                $affectedRemoved
            )));
            $repair = array_values(array_diff($repair, $memberIds));
            $repair = self::pruneRosterRepairIdsAgainstMatchRoster($repair, $teamId, $matchId, $gameType);

            $group->status = 'inactive';
            $group->roster_repair_player_ids = $repair === [] ? null : $repair;
            $group->saveQuietly();
            $updated++;
        }

        return $updated;
    }

    /**
     * Run when opening Configure → Players Grouping: demote invalid `active` groups to `inactive`.
     * Does not modify `draft`. Idempotent.
     *
     * @return int Number of groups whose status was changed from active to inactive
     */
    public static function syncStatusesForConfigureLanding(
        int $teamId,
        int $matchId,
        int $gameType
    ): int {
        $isPractice = (int) $gameType === 2;
        $expectedGroupLevel = $isPractice ? 2 : 1;

        $rosterIds = self::matchRosterPlayerIdsForConfigure($teamId, $matchId, $gameType);
        $rosterFlip = array_flip($rosterIds);

        $groups = self::query()
            ->where('team_id', $teamId)
            ->where('game_id', $matchId)
            ->where('group_level', $expectedGroupLevel)
            ->get();

        $updated = 0;

        foreach ($groups as $group) {
            if (! self::shouldDemoteActiveGroupOnRosterRules($group, $isPractice, $rosterFlip)) {
                continue;
            }

            $group->status = 'inactive';
            $group->saveQuietly();
            $updated++;
        }

        return $updated;
    }

    /** League roster size for non-practice personal groups (defaults to 12). */
    public static function leagueNonPracticePlayerLimitForGroup(self $group): int
    {
        $lim = 12;
        if ($group->league_id) {
            $league = League::query()->find((int) $group->league_id);
            if ($league !== null && (int) ($league->number_of_players ?? 0) > 0) {
                $lim = (int) $league->number_of_players;
            }
        }

        return $lim;
    }

    /**
     * Model `saving` hook: keep `active` only when on-roster member count matches league/practice rules
     * and no roster-repair players are pending.
     */
    protected function applyActiveStatusGuardOnSave(): void
    {
        $stored = strtolower((string) ($this->status ?? 'active'));
        if ($stored !== 'active') {
            return;
        }

        $isPractice = (int) ($this->group_level ?? 1) === 2;
        $gameType = $isPractice ? 2 : 1;
        $teamId = (int) $this->team_id;
        $matchId = (int) $this->game_id;
        $rosterFlip = array_flip(self::matchRosterPlayerIdsForConfigure($teamId, $matchId, $gameType));

        if (self::shouldDemoteActiveGroupOnRosterRules($this, $isPractice, $rosterFlip)) {
            $this->status = 'inactive';
        }
    }

    /**
     * @param  array<int,true>  $rosterFlip
     */
    private static function shouldDemoteActiveGroupOnRosterRules(
        self $group,
        bool $isPractice,
        array $rosterFlip
    ): bool {
        $stored = strtolower((string) ($group->status ?? 'active'));
        if ($stored === 'draft' || $stored !== 'active') {
            return false;
        }

        $teamId = (int) $group->team_id;
        $matchId = (int) $group->game_id;
        $gameType = $isPractice ? 2 : 1;

        $repair = array_values(array_filter(array_map('intval', $group->roster_repair_player_ids ?? [])));
        $repair = self::pruneRosterRepairIdsAgainstMatchRoster($repair, $teamId, $matchId, $gameType);
        if ($repair !== []) {
            return true;
        }

        $n = self::countGroupMembersOnMatchRosterFromPayload($group, $isPractice, $rosterFlip);

        if ($isPractice) {
            return ! self::practiceGroupActiveMemberCountIsValid($n);
        }

        return $n !== self::leagueNonPracticePlayerLimitForGroup($group);
    }

    /**
     * Match end: delete configure-player rows except those in an **active** personal group and
     * still on the match configure roster (same rule as coach-vue `collectActiveGroupedPlayerIdsBySide`).
     * Historic group members shown as "Not in the match" are not kept. `special` rows are preserved.
     *
     * Run after {@see syncInvalidActivePracticeGroupStatusesForMatchEnd} so demoted groups are excluded.
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

            $rosterFlip = array_flip(
                self::matchRosterPlayerIdsForConfigureResolved(
                    $side['team_id'],
                    $matchId,
                    $configureGameType
                )
            );

            $idsBySide = self::groupedPlayerIdsByConfigureSideFromGroups(
                $side['team_id'],
                $matchId,
                $expectedGroupLevel,
                $isPracticeConfigure,
                $rosterFlip,
                true
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
        bool $usePracticePlayersPayload,
        ?array $rosterFlip = null,
        bool $onlyActiveGroups = false
    ): array {
        $offensiveFlip = [];
        $defensiveFlip = [];

        $groups = self::query()
            ->where('team_id', $teamId)
            ->where('game_id', $gameId)
            ->where('group_level', $expectedGroupLevel)
            ->get();

        foreach ($groups as $group) {
            if ($onlyActiveGroups && strtolower((string) ($group->status ?? '')) !== 'active') {
                continue;
            }

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
                if ($rosterFlip !== null && ! isset($rosterFlip[(int) $mid])) {
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
