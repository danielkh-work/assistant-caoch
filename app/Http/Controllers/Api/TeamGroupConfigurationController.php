<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\LeagueTeam;
use App\Models\TeamGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamGroupConfigurationController extends Controller
{
    public function sync(Request $request, int $teamId)
    {
        $validated = $request->validate([
            'group_ids' => ['required', 'array'],
            'group_ids.*' => ['integer'],
        ]);

        $team = LeagueTeam::query()->find($teamId);
        if (! $team) {
            return new BaseResponse(422, 422, 'Team not found.');
        }

        $groupIds = collect($validated['group_ids'])
            ->map(fn ($groupId) => (int) $groupId)
            ->unique()
            ->values();

        $groups = TeamGroup::query()
            ->where('team_id', $team->id)
            ->whereIn('id', $groupIds)
            ->withCount('players')
            ->get()
            ->keyBy('id');

        $missingIds = $groupIds->diff($groups->keys()->map(fn ($groupId) => (int) $groupId)->values());

        if ($missingIds->isNotEmpty()) {
            return new BaseResponse(
                422,
                422,
                'One or more groups do not belong to the specified team.',
                ['invalid_group_ids' => $missingIds->values()->all()]
            );
        }

        $invalidGroups = [];
        foreach ($groups as $group) {
            $reasons = [];

            if ($group->status !== 'active') {
                $reasons[] = 'status must be active';
            }

            if (! in_array((int) $group->segment, [7, 11, 12], true)) {
                $reasons[] = 'segment must be 7, 11, or 12';
            }

            if ((int) $group->players_count !== (int) $group->segment) {
                $reasons[] = 'player count must match the group segment';
            }

            if ($reasons !== []) {
                $invalidGroups[] = [
                    'id' => (int) $group->id,
                    'reasons' => $reasons,
                ];
            }
        }

        if ($invalidGroups !== []) {
            return new BaseResponse(
                422,
                422,
                'One or more groups are not eligible for configuration.',
                ['invalid_groups' => $invalidGroups]
            );
        }

        DB::beginTransaction();

        try {
            $team->configuredGroups()->sync($groupIds->all());

            DB::commit();

            return new BaseResponse(
                200,
                200,
                'Team group configuration synced successfully',
                $team->load(['configuredGroups.players'])
            );
        } catch (\Throwable $th) {
            DB::rollBack();

            return new BaseResponse(
                422,
                422,
                $th->getMessage()
            );
        }
    }
}
