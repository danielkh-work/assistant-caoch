<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\LeagueTeam;
use App\Models\TeamGroup;
use App\Models\TeamPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamGroupController extends Controller
{
    public function index(Request $request, $teamId = null)
    {
        $effectiveTeamId = $teamId;
        $query = TeamGroup::query()->with(['players', 'configuredTeams']);

        if ($effectiveTeamId) {
            $query->where('team_id', $effectiveTeamId);
        }

        $name = trim((string) ($request->query('name', '') ?: $request->query('search', '')));
        if ($name !== '') {
            $query->where('name', 'like', '%' . addcslashes($name, '%_\\') . '%');
        }

        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $query->where('status', strtolower($status));
        }

        $type = trim((string) $request->query('type', ''));
        if ($type !== '') {
            $query->where('type', strtolower($type));
        }

        $segment = $request->query('segment');
        if ($segment !== null && $segment !== '') {
            $query->where('segment', (int) $segment);
        }

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'Team groups fetched successfully',
            $query->orderByDesc('created_at')->get()
        );
    }

    public function store(Request $request, $teamId)
    {
        $validated = $request->validate([
            'team_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:offense,defense'],
            'segment' => ['required', 'integer', 'in:7,11,12'],
            'status' => ['nullable', 'in:active,inactive,draft'],
            'player_ids' => ['nullable', 'array'],
            'player_ids.*' => ['integer'],
        ]);

        if (! LeagueTeam::whereKey($teamId)->exists()) {
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, 'Team not found.');
        }

        if (isset($validated['team_id']) && (int) $validated['team_id'] !== (int) $teamId) {
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, 'team_id does not match the route team.');
        }

        DB::beginTransaction();

        try {
            $requestedStatus = $validated['status'] ?? 'draft';

            // Create as draft first so the saving hook doesn't validate
            // player count before we've had a chance to sync players.
            $group = TeamGroup::create([
                'team_id' => (int) $teamId,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'type' => strtolower($validated['type']),
                'segment' => (int) $validated['segment'],
                'status' => 'draft',
            ]);

            // Sync players first, then apply the requested status so the
            // hook can validate the correct player count.
            $group->players()->sync($this->normalizePlayerIds($request->input('player_ids', []), (int) $teamId));

            if ($requestedStatus !== 'draft') {
                $group->status = $requestedStatus;
                $group->save();
            }

            DB::commit();

            return new BaseResponse(
                STATUS_CODE_OK,
                STATUS_CODE_OK,
                'Team group created successfully',
                $group->load(['players'])
            );
        } catch (\Throwable $th) {
            DB::rollBack();

            return new BaseResponse(
                STATUS_CODE_UNPROCESSABLE,
                STATUS_CODE_UNPROCESSABLE,
                $th->getMessage()
            );
        }
    }

    public function update(Request $request, $teamId, $groupId)
    {
        $validated = $request->validate([
            'team_id' => ['nullable', 'integer'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'required', 'in:offense,defense'],
            'segment' => ['sometimes', 'required', 'integer', 'in:7,11,12'],
            'status' => ['sometimes', 'required', 'in:active,inactive,draft'],
            'player_ids' => ['nullable', 'array'],
            'player_ids.*' => ['integer'],
        ]);

        $group = TeamGroup::find($groupId);
        if (! $group) {
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, 'Group not found.');
        }

        if ((int) $teamId !== (int) $group->team_id) {
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, 'team_id does not match the group team.');
        }

        if (isset($validated['team_id']) && (int) $validated['team_id'] !== (int) $group->team_id) {
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, 'team_id does not match the group team.');
        }

        DB::beginTransaction();

        try {
            $pendingStatus = array_key_exists('status', $validated) ? $validated['status'] : null;
            $previousStatus = $group->status;

            if (array_key_exists('name', $validated)) {
                $group->name = $validated['name'];
            }
            if (array_key_exists('description', $validated)) {
                $group->description = $validated['description'];
            }
            if (array_key_exists('type', $validated)) {
                $group->type = strtolower($validated['type']);
            }
            if (array_key_exists('segment', $validated)) {
                $group->segment = (int) $validated['segment'];
            }

            // Temporarily force draft so the saving hook doesn't validate
            // player count before we sync players below.
            $group->status = 'draft';
            $group->save();

            // Sync players first so ensureValidActivation sees the correct count.
            if ($request->has('player_ids')) {
                $group->players()->sync($this->normalizePlayerIds($request->input('player_ids', []), (int) $group->team_id));
            }

            // Now apply the final status (triggers hook with correct player count).
            $finalStatus = $pendingStatus ?? $previousStatus;
            if ($group->status !== $finalStatus) {
                $group->status = $finalStatus;
                $group->save();
            }

            DB::commit();

            return new BaseResponse(
                STATUS_CODE_OK,
                STATUS_CODE_OK,
                'Team group updated successfully',
                $group->load(['players'])
            );
        } catch (\Throwable $th) {
            DB::rollBack();

            return new BaseResponse(
                STATUS_CODE_UNPROCESSABLE,
                STATUS_CODE_UNPROCESSABLE,
                $th->getMessage()
            );
        }
    }

    public function destroy(Request $request, $teamId = null, $groupId = null)
    {
        if ($groupId === null) {
            $groupId = $teamId;
            $teamId = null;
        }

        $group = TeamGroup::find($groupId);
        if (! $group) {
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, 'Group not found.');
        }

        if ($teamId !== null && (int) $teamId !== (int) $group->team_id) {
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, 'team_id does not match the group team.');
        }

        $group->delete();

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'Team group deleted successfully'
        );
    }

    /**
     * @param  array<int,mixed>  $playerIds
     * @return array<int,int>
     */
    private function normalizePlayerIds(array $playerIds, int $teamId): array
    {
        $ids = collect($playerIds)
            ->filter(fn ($id) => is_int($id) || (is_string($id) && ctype_digit($id)))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (! count($ids)) {
            return [];
        }

        return TeamPlayer::query()
            ->where('team_id', $teamId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
