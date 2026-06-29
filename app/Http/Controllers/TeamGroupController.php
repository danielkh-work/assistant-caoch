<?php

namespace App\Http\Controllers;

use App\Http\Responses\BaseResponse;
use App\Models\TeamGroup;
use Illuminate\Http\Request;

class TeamGroupController extends Controller
{
    public function index(int $teamId)
    {
        $groups = TeamGroup::where('team_id', $teamId)
            ->orderBy('created_at', 'desc')
            ->get();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, 'Team groups fetched', $groups);
    }

    public function store(Request $request, int $teamId)
    {
        $request->validate([
            'group_name'      => 'required|string|max:255',
            'description'     => 'nullable|string',
            'type'            => 'required|string',
            'group_level'     => 'required|integer|in:7,11,12',
            'status'          => 'nullable|string',
            'player_ids'      => 'nullable|array',
            'player_ids.*'    => 'integer',
            'player_positions'=> 'nullable|array',
            'league_id'       => 'nullable|integer',
        ]);

        $positionsMap = $request->player_positions ?? [];
        $players = collect($request->player_ids ?? [])
            ->map(fn ($id) => ['id' => (int) $id, 'positions' => $positionsMap[(string)(int)$id] ?? null])
            ->values()
            ->all();

        $playerCount     = count($players);
        $groupLevel      = (int) $request->group_level;
        $requestedStatus = $request->status ?? 'active';
        $finalStatus     = ($requestedStatus === 'active' && $playerCount !== $groupLevel)
            ? 'inactive'
            : $requestedStatus;

        $group = TeamGroup::create([
            'team_id'     => $teamId,
            'league_id'   => $request->league_id,
            'group_name'  => $request->group_name,
            'description' => $request->description,
            'type'        => $request->type,
            'group_level' => $groupLevel,
            'status'      => $finalStatus,
            'players'     => $players ?: null,
        ]);

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, 'Team group created', $group);
    }

    public function update(Request $request, int $id)
    {
        $group = TeamGroup::findOrFail($id);

        $request->validate([
            'group_name'      => 'required|string|max:255',
            'description'     => 'nullable|string',
            'type'            => 'required|string',
            'group_level'     => 'required|integer|in:7,11,12',
            'status'          => 'nullable|string',
            'player_ids'      => 'nullable|array',
            'player_ids.*'    => 'integer',
            'player_positions'=> 'nullable|array',
        ]);

        $positionsMap = $request->player_positions ?? [];
        $players = collect($request->player_ids ?? [])
            ->map(fn ($id) => ['id' => (int) $id, 'positions' => $positionsMap[(string)(int)$id] ?? null])
            ->values()
            ->all();

        $requestedStatus = $request->status ?? $group->status;
        $playerCount = count($players);
        $groupLevel  = (int) $request->group_level;
        // Cannot be active if player count doesn't match the group size
        $finalStatus = ($requestedStatus === 'active' && $playerCount !== $groupLevel)
            ? 'inactive'
            : $requestedStatus;

        $group->update([
            'group_name'  => $request->group_name,
            'description' => $request->description,
            'type'        => $request->type,
            'group_level' => $groupLevel,
            'status'      => $finalStatus,
            'players'     => $players ?: null,
        ]);

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, 'Team group updated', $group->fresh());
    }

    public function destroy(int $id)
    {
        $group = TeamGroup::findOrFail($id);
        $group->delete();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, 'Team group deleted');
    }

    public function players(int $teamId)
    {
        $team = \App\Models\LeagueTeam::findOrFail($teamId);

        if ((int) ($team->is_practice ?? 0) === 1) {
            $ptpRows = \App\Models\PracticeTeamPlayer::where('team_id', $teamId)->get();

            // Two-source positions lookup.
            // Source 1: practice team's OWN team_players (covers players added directly
            //   via Add Player — their positions live under team_id=practice_team_id).
            // Source 2: league's My Team (type=1, not practice) — covers players imported
            //   from the main roster. My Team data overrides Source 1 when non-empty.
            // PTP.player_id may be TeamPlayer.id OR players.id depending on when the
            // practice team was created, so we index by both keys.
            $positionsMap = [];

            $practiceOwnTPs = \App\Models\TeamPlayer::where('team_id', $teamId)
                ->with(['teamPlayerPosition' => fn ($q) => $q->orderBy('sort')])
                ->get();

            foreach ($practiceOwnTPs as $tp) {
                $pos = $tp->teamPlayerPosition->pluck('position_name')->filter()->values()->all();
                $positionsMap['id:' . $tp->id] = $pos;
                if ($tp->player_id) {
                    $positionsMap['pid:' . $tp->player_id] = $pos;
                }
            }

            $myTeam = \App\Models\LeagueTeam::where('league_id', $team->league_id)
                ->where('type', 1)
                ->where(function ($q) { $q->where('is_practice', 0)->orWhereNull('is_practice'); })
                ->first();

            if ($myTeam) {
                $myTPs = \App\Models\TeamPlayer::where('team_id', $myTeam->id)
                    ->with(['teamPlayerPosition' => fn ($q) => $q->orderBy('sort')])
                    ->get();

                foreach ($myTPs as $tp) {
                    $pos = $tp->teamPlayerPosition->pluck('position_name')->filter()->values()->all();
                    // My Team overrides practice-team entry only when it has richer data
                    if (!empty($pos) || !isset($positionsMap['id:' . $tp->id])) {
                        $positionsMap['id:' . $tp->id] = $pos;
                    }
                    if ($tp->player_id && (!empty($pos) || !isset($positionsMap['pid:' . $tp->player_id]))) {
                        $positionsMap['pid:' . $tp->player_id] = $pos;
                    }
                }
            }

            // Deduplicate by name, preferring records with positions.
            // Some practice teams store each player twice (orphaned + valid FK).
            $byName = [];
            foreach ($ptpRows as $p) {
                $positions = $positionsMap['pid:' . $p->player_id]
                    ?? $positionsMap['id:' . $p->player_id]
                    ?? [];

                $nameKey = mb_strtolower(trim($p->name ?? ''));

                $entry = [
                    'id'             => $p->id,
                    'name'           => $p->name,
                    'number'         => $p->number,
                    'position'       => $p->position,
                    'position_value' => $p->position_value,
                    'rpp'            => $p->rpp,
                    'type'           => $p->type,
                    'positions'      => $positions,
                ];

                if (!isset($byName[$nameKey]) || (!empty($positions) && empty($byName[$nameKey]['positions']))) {
                    $byName[$nameKey] = $entry;
                }
            }

            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, 'Players fetched', array_values($byName));
        }

        $rows = \App\Models\TeamPlayer::where('team_id', $teamId)
            ->with(['teamPlayerPosition' => fn ($q) => $q->orderBy('sort')])
            ->get();

        $players = $rows->map(function ($p) {
            return [
                'id'             => $p->id,
                'name'           => $p->name ?? $p->player_name ?? null,
                'number'         => $p->number ?? null,
                'position'       => $p->position ?? $p->player_position ?? null,
                'position_value' => $p->position_value ?? null,
                'rpp'            => $p->rpp ?? null,
                'type'           => $p->type ?? null,
                'positions'      => isset($p->teamPlayerPosition)
                    ? $p->teamPlayerPosition->pluck('position_name')->filter()->values()->all()
                    : [],
            ];
        })->values();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, 'Players fetched', $players);
    }

    public function importToGame(Request $request, int $gameId)
    {
        $request->validate([
            'group_ids'    => 'required|array',
            'group_ids.*'  => 'integer',
            'team_id'      => 'required|integer',
            'team_type'    => 'nullable|integer',
            'league_id'    => 'nullable|integer',
            'is_practice'  => 'nullable',
        ]);

        $isPractice = filter_var($request->is_practice ?? false, FILTER_VALIDATE_BOOLEAN);
        $teamType   = (int) ($request->team_type ?? 1);
        $gameType   = $isPractice ? 2 : 1;
        $playerCol  = $isPractice ? 'practice_player_id' : 'player_id';
        $created = [];
        $skipped = [];

        foreach ($request->group_ids as $groupId) {
            $teamGroup = TeamGroup::find((int) $groupId);
            if (! $teamGroup) {
                $skipped[] = $groupId;
                continue;
            }

            $alreadyExists = \App\Models\PersionalGrouping::where('game_id', $gameId)
                ->whereNotNull('source_team_group_id')
                ->where('source_team_group_id', $teamGroup->id)
                ->exists();

            if ($alreadyExists) {
                $skipped[] = $groupId;
                continue;
            }

            $gameGroup = \App\Models\PersionalGrouping::withoutEvents(function () use ($teamGroup, $gameId, $request, $isPractice) {
                return \App\Models\PersionalGrouping::create([
                    'game_id'                  => $gameId,
                    'team_id'                  => (int) $request->team_id,
                    'league_id'                => $request->league_id ? (int) $request->league_id : null,
                    'group_name'               => $teamGroup->group_name,
                    'type'                     => $teamGroup->type,
                    'players'                  => $isPractice ? null : $teamGroup->players,
                    'practice_players'         => $isPractice ? $teamGroup->players : null,
                    'group_level'              => $isPractice ? 2 : 1,
                    'status'                   => 'active',
                    'source_team_group_id'     => $teamGroup->id,
                    'roster_repair_player_ids' => null,
                ]);
            });

            $rawPlayers = $teamGroup->players;
            $slotType   = str_contains(strtolower($teamGroup->type ?? ''), 'offen') ? 'offensive' : 'defensive';
            $playerIds  = collect(is_array($rawPlayers) ? $rawPlayers : [])
                ->map(fn ($p) => is_array($p) ? (int) ($p['id'] ?? 0) : (int) $p)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            // Only create configure rows if the group has the correct roster size.
            // A group with the wrong size will be demoted to inactive by syncStatusesForConfigureLanding
            // on the next fetch; demote it immediately here so configure rows are never created for it.
            $playerCount  = count($playerIds);
            $groupIsValid = $isPractice
                ? in_array($playerCount, [7, 11, 12])
                : ($playerCount === \App\Models\PersionalGrouping::leagueNonPracticePlayerLimitForGroup($gameGroup));

            if (!$groupIsValid) {
                $gameGroup->status = 'inactive';
                $gameGroup->saveQuietly();
                $created[] = $gameGroup->fresh();
                continue;
            }

            foreach ($playerIds as $playerId) {
                \App\Models\ConfiguredPlayingTeamPlayer::firstOrCreate(
                    [
                        'match_id'  => $gameId,
                        'team_id'   => (int) $request->team_id,
                        'team_type' => $teamType,
                        'game_type' => $gameType,
                        $playerCol  => $playerId,
                    ],
                    ['type' => $slotType]
                );
            }

            $created[] = $gameGroup->fresh();
        }

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'Groups imported',
            ['created' => $created, 'skipped' => $skipped]
        );
    }
}
