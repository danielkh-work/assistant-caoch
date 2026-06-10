<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\LeagueTeam;
use App\Support\LeagueOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeagueController extends Controller
{
    private const UPDATABLE_FIELDS = [
        'sport_id',
        'league_rule_id',
        'title',
        'location',
        'number_of_team',
        'number_of_downs',
        'length_of_field',
        'number_of_timeouts',
        'clock_time',
        'number_of_quarters',
        'length_of_quarters',
        'stop_time_reason',
        'overtime_rules',
        'number_of_players',
        'practice_number_players',
        'warning_time_minutes',
        'flag_tbd',
        'rpp_configuration',
    ];

    public function update(Request $request, int $league): BaseResponse
    {
        $validated = $request->validate([
            'sport_id' => ['sometimes', 'integer', 'exists:sports,id'],
            'league_rule_id' => ['sometimes', 'integer', 'exists:league_rules,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'number_of_team' => ['sometimes', 'nullable', 'integer'],
            'number_of_downs' => ['sometimes', 'nullable', 'string', 'max:255'],
            'length_of_field' => ['sometimes', 'nullable', 'string', 'max:255'],
            'number_of_timeouts' => ['sometimes', 'nullable', 'integer'],
            'clock_time' => ['sometimes', 'nullable', 'string', 'max:255'],
            'number_of_quarters' => ['sometimes', 'nullable', 'integer'],
            'length_of_quarters' => ['sometimes', 'nullable', 'integer'],
            'stop_time_reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'overtime_rules' => ['sometimes', 'nullable', 'integer'],
            'number_of_players' => ['sometimes', 'nullable', 'integer'],
            'practice_number_players' => ['sometimes', 'nullable', 'integer'],
            'warning_time_minutes' => ['sometimes', 'nullable', 'integer'],
            'flag_tbd' => ['sometimes', 'nullable', 'string', 'max:255'],
            'rpp_configuration' => ['sometimes', 'nullable', Rule::in(['with_rpp', 'without_rpp'])],
            'team_name' => ['sometimes', 'array'],
            'team_name.*.name' => ['required_with:team_name', 'string', 'max:255'],
            'team_name.*.id' => ['sometimes', 'nullable', 'integer', 'exists:league_teams,id'],
        ]);

        $leagueFields = collect($validated)->only(self::UPDATABLE_FIELDS);
        $hasTeamUpdates = array_key_exists('team_name', $validated);

        if ($leagueFields->isEmpty() && ! $hasTeamUpdates) {
            throw ValidationException::withMessages([
                'payload' => 'At least one league field or team_name must be provided.',
            ]);
        }

        $leagueModel = LeagueOwnership::leagueForHeadCoach($league);

        DB::beginTransaction();

        try {
            if ($leagueFields->isNotEmpty()) {
                $leagueModel->fill($leagueFields->all());
                $leagueModel->save();
            }

            if ($hasTeamUpdates) {
                foreach ($validated['team_name'] as $index => $teamData) {
                    if (empty($teamData['name'])) {
                        continue;
                    }

                    $team = isset($teamData['id'])
                        ? LeagueTeam::query()
                            ->whereKey($teamData['id'])
                            ->where('league_id', $leagueModel->id)
                            ->first() ?? new LeagueTeam
                        : new LeagueTeam;

                    $team->league_id = $leagueModel->id;
                    $team->team_name = $teamData['name'];
                    $team->type = $index === 0 ? 1 : null;
                    $team->save();
                }
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            return new BaseResponse(
                STATUS_CODE_BADREQUEST,
                STATUS_CODE_BADREQUEST,
                $th->getMessage(),
            );
        }

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'League updated successfully',
            $leagueModel->fresh()->load(['teams', 'league_rule', 'sport']),
        );
    }
}
