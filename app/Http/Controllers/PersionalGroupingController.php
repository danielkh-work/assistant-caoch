<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PersionalGrouping;
use App\Models\TeamPlayer;
use App\Models\PracticeTeamPlayer;

use Illuminate\Support\Facades\DB;
use App\Http\Responses\BaseResponse;
class PersionalGroupingController extends Controller
{


public function storeAllGroups(Request $request)
{
    $groupsData = $request->all();

    DB::beginTransaction();

    try {
        $insertData = [];

        foreach ($groupsData as $groupData) {

            $isPractice = filter_var($groupData['is_practice'] ?? false, FILTER_VALIDATE_BOOLEAN);
            \Log::info(['is_practice'=> $isPractice ]);

            $status = $groupData['status'] ?? 'active';
            if (! in_array($status, ['draft', 'active', 'inactive'], true)) {
                $status = 'active';
            }

                if ($isPractice && $status === 'active') {
                    $normalized = $this->normalizeGroupPlayers($groupData['players'] ?? []);
                    $nRoster = PersionalGrouping::countNormalizedPlayersOnMatchRoster(
                        $normalized,
                        (int) $groupData['team_id'],
                        (int) $groupData['game_id'],
                        2
                    );
                    if (! PersionalGrouping::practiceGroupActiveMemberCountIsValid($nRoster)) {
                        DB::rollBack();

                        return new BaseResponse(
                            STATUS_CODE_ERROR,
                            STATUS_CODE_ERROR,
                            'Practice groups can only be Active when exactly 7, 11, or 12 members are on the match roster for this game. '
                                ."Currently {$nRoster} match-rostered player(s) count toward this group (Configure Players).",
                        );
                    }
                }

            $insertData[] = [
                'game_id' => $groupData['game_id'],
                'league_id' => $groupData['league_id'],
                'team_id' => $groupData['team_id'],
                'group_name' => $groupData['group_name'],
                'type' => $groupData['type'] ?? 'Offense',

                // ✅ If practice → store in practice_players
                'players' => $isPractice ? null : json_encode($groupData['players']),
                'practice_players' => $isPractice ? json_encode($groupData['players']) : null,
                'group_level' => $isPractice ? 2 : 1, // 2 = Practice Mode, 1 = Play Mode
                'status' => $status,
                'roster_repair_player_ids' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        PersionalGrouping::insert($insertData);

        DB::commit();

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            "Groups saved successfully",
            $groupsData
        );

    } catch (\Exception $e) {

        DB::rollBack();

        return new BaseResponse(
            STATUS_CODE_ERROR,
            STATUS_CODE_ERROR,
            "Failed to save groups: " . $e->getMessage()
        );
    }
}

//     public function storeAllGroups(Request $request)
// {
//     $groupsData = $request->all(); // array of groups

//     DB::beginTransaction();

//     try {
//         $insertData = [];

//         foreach ($groupsData as $groupData) {
//             $insertData[] = [
//                 'game_id' => $groupData['game_id'],
//                 'league_id' => $groupData['league_id'],
//                 'team_id' => $groupData['team_id'],
//                 'group_name' => $groupData['group_name'],
//                 'type' => $groupData['type'] ?? 'Offense',
//                 'players' => json_encode($groupData['players']), // convert array to JSON
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ];
//         }

//         PersionalGrouping::insert($insertData); // bulk insert

//         DB::commit();

//         return new BaseResponse(
//             STATUS_CODE_OK,
//             STATUS_CODE_OK,
//             "Groups saved successfully",
//             $groupsData // you can return original data if needed
//         );

//     } catch (\Exception $e) {
//         DB::rollBack();

//         return new BaseResponse(
//             STATUS_CODE_ERROR,
//             STATUS_CODE_ERROR,
//             "Failed to save groups: " . $e->getMessage()
//         );
//     }
// }

// public function updateGroup(Request $request, $id)
// {
//     $request->validate([
//         'group_name' => 'required|string|max:255',

//         'players'    => 'required|array',
//     ]);

//     try {
//         $group = PersionalGrouping::findOrFail($id);


//         $group->update([
//             'group_name' => $request->group_name,

//             'players'    => $request->players,
//         ]);

//         return new BaseResponse(
//             STATUS_CODE_OK,
//             STATUS_CODE_OK,
//             "Group updated successfully",
//             $group
//         );

//     } catch (\Exception $e) {
//         return new BaseResponse(
//             STATUS_CODE_ERROR,
//             STATUS_CODE_ERROR,
//             "Failed to update group: " . $e->getMessage()
//         );
//     }
// }

    /**
     * @param  array<int,mixed>  $players
     * @return array<int,array{id:int,mixed}>
     */
    protected function normalizeGroupPlayers(array $players): array
    {
        $out = [];
        foreach ($players as $p) {
            if (is_int($p) || (is_string($p) && ctype_digit($p))) {
                $out[] = ['id' => (int) $p, 'positions' => null];
                continue;
            }
            if (is_array($p) && isset($p['id'])) {
                $out[] = [
                    'id' => (int) $p['id'],
                    'positions' => $p['positions'] ?? null,
                ];
            }
        }

        return $out;
    }

        public function updateGroup(Request $request, $id)
        {
            $request->validate([
                'group_name'  => 'required|string|max:255',
                // `required` rejects []; inactive groups may legitimately save with no match-rostered members
                // when selections are orphans / "Not in the match" (payload is empty after normalization).
                'players'     => 'present|array',
                'is_practice' => 'nullable',
                'status' => 'nullable|in:draft,active,inactive',
                'roster_repair_append' => 'nullable|array',
                'roster_repair_append.*' => 'integer',
            ]);

            try {
                $group = PersionalGrouping::findOrFail($id);

                $isPractice = filter_var($request->is_practice ?? false, FILTER_VALIDATE_BOOLEAN);

                $normalized = $this->normalizeGroupPlayers($request->players);
                $memberIds = collect($normalized)->pluck('id')->map(fn ($id) => (int) $id)->all();

                $append = $request->input('roster_repair_append', []);
                $repair = array_values(array_unique(array_merge(
                    $group->roster_repair_player_ids ?? [],
                    array_map('intval', $append)
                )));
                $repair = array_values(array_diff($repair, $memberIds));

                $isPracticeGroup = (int) ($group->group_level ?? 1) === 2;
                $gameType = $isPracticeGroup ? 2 : 1;
                $repair = PersionalGrouping::pruneRosterRepairIdsAgainstMatchRoster(
                    $repair,
                    (int) $group->team_id,
                    (int) $group->game_id,
                    $gameType
                );

                $newStatus = $request->input('status');
                if ($newStatus === 'active' && count($repair) > 0) {
                    return new BaseResponse(
                        STATUS_CODE_ERROR,
                        STATUS_CODE_ERROR,
                        'Cannot set group to active until all removed roster players are added back to the group.',
                    );
                }

                $resolvedStatus = ($newStatus !== null && $newStatus !== '')
                    ? strtolower((string) $newStatus)
                    : strtolower((string) ($group->status ?? 'active'));
                if ($isPracticeGroup && $resolvedStatus === 'active') {
                    $nRoster = PersionalGrouping::countNormalizedPlayersOnMatchRoster(
                        $normalized,
                        (int) $group->team_id,
                        (int) $group->game_id,
                        $gameType
                    );
                    if (! PersionalGrouping::practiceGroupActiveMemberCountIsValid($nRoster)) {
                        return new BaseResponse(
                            STATUS_CODE_ERROR,
                            STATUS_CODE_ERROR,
                            'Practice groups can only be Active when exactly 7, 11, or 12 members are on the match roster for this game. '
                                ."Currently {$nRoster} match-rostered player(s) count toward this group (Configure Players).",
                        );
                    }
                }

                if ($resolvedStatus === 'active' && ! $isPracticeGroup && count($memberIds) === 0) {
                    return new BaseResponse(
                        STATUS_CODE_ERROR,
                        STATUS_CODE_ERROR,
                        'Active groups must include players on the match roster.',
                    );
                }

                $updateData = [
                    'group_name' => $request->group_name,
                    'roster_repair_player_ids' => count($repair) ? $repair : null,
                ];

                if ($newStatus !== null && $newStatus !== '') {
                    $updateData['status'] = $newStatus;
                }

                if ($isPractice) {
                    $updateData['players'] = null;
                    $updateData['practice_players'] = $normalized;
                } else {
                    $updateData['players'] = $normalized;
                    $updateData['practice_players'] = null;
                }

                $group->update($updateData);

                return new BaseResponse(
                    STATUS_CODE_OK,
                    STATUS_CODE_OK,
                    "Group updated successfully",
                    $group->fresh()
                );

            } catch (\Exception $e) {

                return new BaseResponse(
                    STATUS_CODE_ERROR,
                    STATUS_CODE_ERROR,
                    "Failed to update group: " . $e->getMessage()
                );
            }
        }

   public function getGroupsByTeamAndGame(Request $request)
    {

       $forPracticeMode = filter_var($request->query('for_practice_mode', false), FILTER_VALIDATE_BOOLEAN);
        $teamId = $request->query('team_id');
        $gameId = $request->query('game_id');
        $leagueId = $request->query('league_id');
        $isPractice = $request->query('is_practice', 0);

        if ((!$teamId || !$gameId) && !$leagueId) {
        return new BaseResponse(
            STATUS_CODE_ERROR,
            STATUS_CODE_ERROR,
            "team_id & game_id OR league_id is required"
        );
    }

      if ($teamId && $gameId) {
          $gameType = (int) $isPractice === 1 ? 2 : 1;
          PersionalGrouping::syncAfterConfigureRosterSave((int) $teamId, (int) $gameId, $gameType);
      }

      $groups = PersionalGrouping::query()
        ->when($teamId && $gameId, function ($q) use ($teamId, $gameId) {
            $q->where('team_id', $teamId)
              ->where('game_id', $gameId);
        })
        ->when((!$teamId || !$gameId) && $leagueId, function ($q) use ($teamId, $leagueId) {
            $q->where('league_id', $leagueId);
            // Always filter by team_id when available to prevent cross-team contamination
            if ($teamId) {
                $q->where('team_id', $teamId);
            }
        })
        // Match-Start / substitution view (for_practice_mode=1) shows the same set as
        // Configure → "Active" tab: every group with status='active'. Practice size 7/11/12 is
        // already validated against the match roster at save time, so a plain JSON_LENGTH check
        // here would wrongly hide groups that legitimately have extra off-roster members tagged
        // "Not in the match" (length can be 8/9 even when 7 roster members are saved).
        ->when($isPractice && $forPracticeMode, function ($q) {
            $q->where('status', 'active')
              ->whereNotNull('practice_players');
        })

        ->orderBy('created_at', 'desc')
        ->get();

        \Log::info(['data request all'=>$groups]);

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            "Groups fetched successfully",
            $groups
        );
    }

   public function deleteGroup($id)
{
    $group = PersionalGrouping::find($id);
    if ($group) {
        $isPractice    = !empty($group->practice_players);
        $playerCol     = $isPractice ? 'practice_player_id' : 'player_id';
        $rawPlayers    = $isPractice ? $group->practice_players : $group->players;

        $deletedPlayerIds = collect(is_array($rawPlayers) ? $rawPlayers : [])
            ->map(fn ($p) => is_array($p) ? (int) ($p['id'] ?? 0) : (int) $p)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $group->delete();

        // Remove configure players that belonged only to this group (not in any remaining group)
        if (!empty($deletedPlayerIds)) {
            $remaining = PersionalGrouping::where('game_id', $group->game_id)
                ->where('team_id', $group->team_id)
                ->get();

            $stillGrouped = $remaining->flatMap(function ($g) use ($isPractice) {
                $raw = $isPractice ? $g->practice_players : $g->players;
                return collect(is_array($raw) ? $raw : [])
                    ->map(fn ($p) => is_array($p) ? (int) ($p['id'] ?? 0) : (int) $p)
                    ->filter(fn ($id) => $id > 0);
            })->unique()->values()->all();

            $toRemove = array_values(array_diff($deletedPlayerIds, $stillGrouped));
            if (!empty($toRemove)) {
                \App\Models\ConfiguredPlayingTeamPlayer::where('match_id', $group->game_id)
                    ->where('team_id', $group->team_id)
                    ->whereIn($playerCol, $toRemove)
                    ->delete();
            }
        }
    }
    return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "group Delete Successfully");
  }

    public function getPlays(PersionalGrouping $group)
    {


        $attachedPlayIds = $group->plays()->pluck('play_id')->toArray();
         $attachedDefPlayIds = $group->defensivePlays()->pluck('defensive_play_id')->toArray();

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            "plays synced successfully",
                [
                        'offensive' => $attachedPlayIds,
                        'defensive' => $attachedDefPlayIds
                ]
        );
    }
    public function syncPlays(Request $request, PersionalGrouping $group)
    {
        // Validate incoming data: 'play_ids' must be an array of integers
         $validated = $request->validate([
           'type' => 'required|in:offense,defensive',
           'play_ids' => 'required|array',
        ]);

        if($request->type=='offense'){
           $group->plays()->sync($validated['play_ids']);
        }else if($request->type=='defensive'){
          $group->defensivePlays()->sync($validated['play_ids']);
        }
       $group->load(['plays', 'defensivePlays']);


        $responseData = [
            'offensive_play_ids' => $group->plays->pluck('id')->toArray(),
            'defensive_play_ids' => $group->defensivePlays->pluck('id')->toArray(),
        ];
        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            "plays synced successfully",
             $responseData
        );
    }

    /**
     * Players still required to re-join the group before it can be set active (roster repair).
     */
    public function rosterRepairMissing(PersionalGrouping $group)
    {
        $repairIds = array_values(array_filter(array_map('intval', $group->roster_repair_player_ids ?? [])));

        $gameType = (int) ($group->group_level ?? 1) === 2 ? 2 : 1;
        $prunedRepair = PersionalGrouping::pruneRosterRepairIdsAgainstMatchRoster(
            $repairIds,
            (int) $group->team_id,
            (int) $group->game_id,
            $gameType
        );
        if ($prunedRepair !== $repairIds) {
            $group->roster_repair_player_ids = count($prunedRepair) ? $prunedRepair : null;
            $group->save();
        }
        $repairIds = $prunedRepair;

        if (! count($repairIds)) {
            return new BaseResponse(
                STATUS_CODE_OK,
                STATUS_CODE_OK,
                'No roster repair pending',
                [
                    'missing_players' => [],
                ]
            );
        }

        $raw = $group->practice_players ?? $group->players;
        $playersArr = is_array($raw) ? $raw : (json_decode($raw ?? '[]', true) ?: []);
        $normalized = $this->normalizeGroupPlayers($playersArr);
        $memberIds = collect($normalized)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $missingIds = array_values(array_diff($repairIds, $memberIds));

        if (! count($missingIds)) {
            return new BaseResponse(
                STATUS_CODE_OK,
                STATUS_CODE_OK,
                'No roster repair pending',
                [
                    'missing_players' => [],
                ]
            );
        }

        $isPracticeGroup = (int) ($group->group_level ?? 1) === 2;

        if ($isPracticeGroup) {
            $rows = PracticeTeamPlayer::query()->whereIn('id', $missingIds)->get()->keyBy('id');
        } else {
            $rows = TeamPlayer::query()->whereIn('id', $missingIds)->get()->keyBy('id');
        }

        $missingPlayers = collect($missingIds)->map(function ($id) use ($rows) {
            $p = $rows->get($id);
            if (! $p) {
                return [
                    'id' => $id,
                    'name' => 'Unknown player',
                    'number' => null,
                ];
            }

            $name = $p->name ?? ($p->player_name ?? null) ?? 'Unknown player';

            return [
                'id' => (int) $p->id,
                'name' => $name,
                'number' => $p->number ?? null,
            ];
        })->values()->all();

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'Roster repair players retrieved',
            [
                'missing_players' => $missingPlayers,
            ]
        );
    }




}





