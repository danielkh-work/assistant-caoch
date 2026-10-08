<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\Play;
use App\Models\OffensiveTargetStrength;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;
use App\Models\PlayTargetOffensivePlayer;
use App\Models\PlayTargetDefensivePlayer;
use App\Models\LeaguePlayOverride;
use App\Models\OffensivePosition;
use App\Models\DefensivePosition;
use App\Models\PlayResult;
use App\Events\PlayResultSubmitted;
use App\Support\BroadcastLeagueResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;


class PlayController extends Controller
{
    private const HMARK_IMAGE_FIELDS = ['hmark_left', 'hmark_center', 'hmark_right'];

    private const HMARK_IMAGE_RULE = 'image|mimes:jpeg,png,jpg,gif,svg,webp';

    private const OFFENSIVE_PLAY_TYPES = ['run', 'pass', 'rpo', 'play_action'];

    private function playPayloadValidationRules(): array
    {
        return [
            'play_name' => 'required|string',
            'playType' => 'required|string|in:' . implode(',', self::OFFENSIVE_PLAY_TYPES),
            'league_id' => 'required|exists:leagues,id',
            'play_type' => 'required',
            'zone_selection' => 'required|integer',
            'min_expected_yard' => 'required|string',
            'max_expected_yard' => 'required|string',
            'target_offensive' => 'required|integer',
            'opposing_defensive' => 'required|integer',
            'pre_snap_motion' => 'required|integer',
            'play_action_fake' => 'required|integer',
            'possession' => 'required|string|in:offensive,defensive',
        ];
    }

    private function playUpdateValidationRules(): array
    {
        $rules = $this->playPayloadValidationRules();
        $rules['playType'] = 'nullable|string|in:' . implode(',', self::OFFENSIVE_PLAY_TYPES);

        return $rules;
    }

    private function normalizePlayTypeRequest(Request $request): void
    {
        if (!$request->has('playType')) {
            return;
        }

        $playType = strtolower(trim((string) $request->playType));

        if ($playType === '' || $playType === 'null' || $playType === 'undefined') {
            $request->request->remove('playType');
            return;
        }

        $request->merge(['playType' => $playType]);
    }

    private function requestHasMeaningfulValue(Request $request, string $key): bool
    {
        if (!$request->has($key)) {
            return false;
        }

        $value = trim((string) $request->input($key));

        return $value !== '' && $value !== 'null' && $value !== 'undefined';
    }

    private function playWriteFailedResponse(\Throwable $e): BaseResponse
    {
        Log::error('Play write failed', [
            'message' => $e->getMessage(),
            'exception' => get_class($e),
        ]);

        return new BaseResponse(
            STATUS_CODE_UNPROCESSABLE,
            STATUS_CODE_UNPROCESSABLE,
            'Unable to save play. Please check your input and try again.'
        );
    }

    private function hmarkImageValidationRules(bool $required = true): array
    {
        $prefix = $required ? 'required|' : 'nullable|';

        return [
            'hmark_left' => $prefix . self::HMARK_IMAGE_RULE,
            'hmark_center' => $prefix . self::HMARK_IMAGE_RULE,
            'hmark_right' => $prefix . self::HMARK_IMAGE_RULE,
        ];
    }

    private function validateHmarkImagesOnUpdate(Request $request, Play $play): void
    {
        $request->validate($this->hmarkImageValidationRules(false));

        $missing = [];
        foreach (self::HMARK_IMAGE_FIELDS as $field) {
            if (!$request->hasFile($field) && empty($play->{$field})) {
                $missing[$field] = ["The {$field} field is required."];
            }
        }

        if (!empty($missing)) {
            throw \Illuminate\Validation\ValidationException::withMessages($missing);
        }
    }

    private function uploadPlayHMarkImage(Request $request, string $field, ?string $oldPath = null): ?string
    {
        if (!$request->hasFile($field)) {
            return null;
        }

        return uploadImage($request->file($field), 'public', $oldPath);
    }

    private function assignHmarkImagesFromRequest(Request $request, Play $play, bool $replaceExisting = false): void
    {
        foreach (self::HMARK_IMAGE_FIELDS as $field) {
            $oldPath = $replaceExisting ? $play->{$field} : null;
            $path = $this->uploadPlayHMarkImage($request, $field, $oldPath);

            if ($path !== null) {
                $play->{$field} = $path;
            }
        }
    }

    public function index(Request $request)
    {


        $leagueId = (int) $request->league_id;

        $query = Play::with(['roles', 'playResults', 'offensiveTargets'])
            ->leftJoin('league_play_overrides', function ($join) use ($leagueId) {
                $join->on('league_play_overrides.global_play_id', '=', 'plays.id')
                    ->where('league_play_overrides.league_id', '=', $leagueId);
            })
            ->where(function ($sub) use ($leagueId) {
                // A Global Play this league has no override row for (not hidden,
                // not customized - customized ones are already covered by the
                // branch below, since the clone itself carries this league_id).
                $sub->where(function ($q) {
                    $q->where('plays.is_global', true)
                        ->whereNull('league_play_overrides.id');
                })
                ->orWhere('plays.league_id', $leagueId);
            })
            ->select('plays.*')
            ->withCount([
                'playResults as win_result' => function ($q) {
                    $q->where('result', 'win')->where('is_practice', 0);
                },
                'playResults as loss_result' => function ($q) {
                    $q->where('result', 'loss')->where('is_practice', 0);
                },
                'playResults as practice_win_result' => function ($q) {
                    $q->where('result', 'win')->where('is_practice', 1);
                },
                'playResults as practice_loss_result' => function ($q) {
                    $q->where('result', 'loss')->where('is_practice', 1);
                },
                'playResults as total_count' => function ($q) {
                    $q->where('is_practice', 0);
                },
                'playResults as total_practice_count' => function ($q) {
                    $q->where('is_practice', 1);
                },
            ])
            ->withAvg('playResults as yardage_difference', 'yardage_difference');

        if($request->sort == "win_result"){
            $query = $query->orderByDesc('win_result');
        }else{
            $query = $query->latest('plays.created_at');
        }

        $searchTerm = trim((string) $request->input('search', ''));
        if ($searchTerm !== '') {
            $needle = '%' . addcslashes($searchTerm, '%_\\') . '%';
            $query->where('play_name', 'like', $needle);
        }


        //TODO improve pagination logic by using laravel standard function
        $paginateRequested = $request->has('page')
            || $request->has('per_page')
            || $request->filled('search');

        if ($paginateRequested) {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = max(1, min(100, (int) $request->input('per_page', 6)));

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            $pagination = [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ];

            return new BaseResponse(
                STATUS_CODE_OK,
                STATUS_CODE_OK,
                'Play Uploaded List ',
                $paginator->items(),
                null,
                null,
                $pagination,
            );
        }

        $play = $query->get();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, 'Play Uploaded List ', $play);
    }

    public function deletePlayResults($id)
    {

        $play = PlayResult::where('play_id', $id)
                ->where('result', 'win');
            if ($play)
                $play->delete();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play success has been reset successfully");


    }




    public function store(Request $request)
    {
        $this->normalizePlayTypeRequest($request);
        $request->validate(array_merge($this->hmarkImageValidationRules(), $this->playPayloadValidationRules()));
        DB::beginTransaction();

        try {
            $play = new Play();
            $play->offensive_play_type = $request->playType;
            $play->play_name = $request->play_name;
            $play->league_id = $request->league_id;
            $play->created_by_user_id = auth()->id();
            $play->play_type = $request->play_type;
            $play->quarter = $request->quarter;
            $play->zone_selection = $request->zone_selection;
            $play->min_expected_yard = $request->min_expected_yard;
            $play->max_expected_yard = $request->max_expected_yard;
            // $play->target_offensive = $request->target_offensive;
            // $play->opposing_defensive = $request->opposing_defensive;
            $play->pre_snap_motion = $request->pre_snap_motion;
            $play->play_action_fake = $request->play_action_fake;

            if (is_array($request->preferred_down)) {
                $play->preferred_down = implode(',', $request->preferred_down);
            } else {
                // If it's a single value or null, just save it directly
                $play->preferred_down = $request->preferred_down;
            }
             if (is_array($request->strategies)) {
                $play->strategies = implode(',', $request->strategies);
            } else {
                // If it's a single value or null, just save it directly
                $play->strategies = $request->strategies;
            }



            $play->possession = $request->possession;
            $play->description = $request->description;
            $play->read_1 = $request->read_2;
            $play->read_2 = $request->read_3;
            $play->position_status = 2;
            $play->video_path = 'video path';
            $this->assignHmarkImagesFromRequest($request, $play);

            // Replace video if uploaded
        if ($request->hasFile('video')) {
            $videoPath = uploadImage($request->file('video'), 'public/uploads/videos');
            $play->video_path = $videoPath;
        }
            $play->save();

            $groups = $request->input('groups', []);
            if (!is_array($groups)) {
                $groups = [];
            }
            $groups = array_values(array_filter($groups, fn($g) => !is_null($g) && $g !== ''));

            if (!empty($groups)) {
                $play->teamGroups()->sync($groups);
            }

            if (is_array($request->offensive)) {
                $offensivePositions = OffensivePosition::pluck('id', 'name')->toArray();
                foreach ($request->offensive as $position => $value) {
                  if ($value === null) {
                        continue; // Skip this entry if the value is null
                    }
                    PlayTargetOffensivePlayer::create([
                        'play_id' => $play->id,
                        'offensive_position_id' => $position,
                        'strength' => $value, // or other columns if needed
                    ]);
                }
            }


            if (is_array($request->defensive)) {
                $defensivePositions = DefensivePosition::pluck('id', 'name')->toArray();

                foreach ($request->defensive as $position => $value) {

                    if ($value === null) {
                            continue; // Skip this entry if the value is null
                        }
                    PlayTargetDefensivePlayer::create([
                        'play_id' => $play->id,
                        'defensive_position_id' => $position,
                        'strength' => $value,
                    ]);
                }
            }

            DB::commit();
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->playWriteFailedResponse($e);
        }


        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play Uploaded Successfully", $play);
    }


    public function duplicatePlay($id)
    {

        $play = Play::findOrFail($id);
        $newPlay = $play->replicate();
        $newPlay->play_name = $play->play_name . ' (Copy)';
        $newPlay->save();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play cloned successfully", $newPlay);
    }


   public function getTargetOffensePosition($playId)
{
    $play = Play::with([
        'targetOffensivePlayers.offensivePosition',
        'offensiveTargets.offensivePosition',
        'offensiveTargets.defensivePosition'
    ])->find($playId);

    if (!$play) {
        return new BaseResponse(STATUS_CODE_NOT_FOUND, STATUS_CODE_NOT_FOUND, "Play not found", null);
    }

    // Group offensiveTargets by offensivePosition code
    $groupedTargets = $play->offensiveTargets->groupBy(function($target) {
        return $target->offensivePosition->code ?? 'unknown';
    });

    // Optional: convert to array to make JSON easier to handle
    $groupedTargetsArray = $groupedTargets->map(function($group) {
        return $group->values(); // reset keys
    });

    return new BaseResponse(
        STATUS_CODE_OK,
        STATUS_CODE_OK,
        "Get target players grouped by code",
        [
            'play' => $play,
            'offensiveTargetsGrouped' => $groupedTargetsArray
        ]
    );
}


    public function update(Request $request, $id)
    {
        $play = Play::findOrFail($id);
        $user = auth()->user();
        $authz = app(\App\Services\PlayAuthorizationService::class);

        // The league this edit is happening in. For a league-owned play this IS
        // $play->league_id; for a Global Play (copy-on-write path) the request
        // must say which league is doing the customizing.
        $targetLeagueId = $play->is_global ? (int) $request->league_id : (int) $play->league_id;

        if (!$authz->canModify($user, $play, $targetLeagueId ?: null)) {
            return response()->json(['message' => 'You are not allowed to modify this play.'], 403);
        }

        $this->normalizePlayTypeRequest($request);
        $request->validate($this->playUpdateValidationRules());
        $this->validateHmarkImagesOnUpdate($request, $play);

        // Captured before the clone swap below: drives both which row gets
        // edited and the hmark-replace decision further down.
        $wasGlobal = $play->is_global;

        DB::beginTransaction();

        try {
            if ($wasGlobal) {
                $target = $this->cloneGlobalPlayForLeague($play, $targetLeagueId, $user);
            } else {
                $target = $play;
            }

            // Deliberately never reassigns $target->league_id from $request->league_id here.
            // canModify() already authorized this edit against $play's OWN league_id - if a
            // head coach owns two leagues, honoring a different league_id from the request
            // would silently move this play into the other league's playbook.
            $target->play_type = $request->play_type;

            if ($request->filled('playType')) {
                $target->offensive_play_type = $request->playType;
            }

            $target->quarter = $request->quarter;
            $target->zone_selection = $request->zone_selection;
            $target->min_expected_yard = $request->min_expected_yard;
            $target->max_expected_yard = $request->max_expected_yard;
            $target->pre_snap_motion = $request->pre_snap_motion;
            $target->play_action_fake = $request->play_action_fake;

            $target->play_name = $request->play_name;

            $target->preferred_down = is_array($request->preferred_down)
                ? implode(',', $request->preferred_down)
                : $request->preferred_down;

            $target->strategies = is_array($request->strategies)
                ? implode(',', $request->strategies)
                : $request->strategies;

            $target->possession = $request->possession;
            $target->description = $request->description;

            if ($this->requestHasMeaningfulValue($request, 'read_2')) {
                $target->read_1 = $request->read_2;
            }

            if ($this->requestHasMeaningfulValue($request, 'read_3')) {
                $target->read_2 = $request->read_3;
            }

            // A freshly-created clone's hmark_* attributes still point at the
            // Global Play's own shared image files (replicate() copied the
            // paths verbatim). uploadImage() physically deletes whatever "old
            // path" it's handed when a new file replaces it, so treating this
            // like a normal in-place replace on the very request that creates
            // the clone would delete the Global Play's image out from under
            // every other league that still shares it. Only pass
            // replaceExisting=true (i.e. actually delete the old file) once
            // the row being edited is genuinely league-owned - either because
            // it was already a league play, or because it's a clone being
            // edited again on a later request (its hmark_* already belongs to
            // it alone by then).
            $this->assignHmarkImagesFromRequest($request, $target, !$wasGlobal);

            // Replace video if uploaded
            if ($request->hasFile('video')) {
                $target->video_path = uploadImage($request->file('video'), 'public/uploads/videos');
            }

            $target->save();


            // Delete old offensive links and recreate
            PlayTargetOffensivePlayer::where('play_id', $target->id)->delete();
            if (is_array($request->offensive)) {
                foreach ($request->offensive as $position => $value) {
                    if ($value === null) {
                        continue; // Skip this entry if the value is null
                    }
                    PlayTargetOffensivePlayer::create([
                        'play_id' => $target->id,
                        'offensive_position_id' => $position,
                        'strength' => $value,
                    ]);
                }
            }

            // Delete old defensive links and recreate
            PlayTargetDefensivePlayer::where('play_id', $target->id)->delete();
            if (is_array($request->defensive)) {
                foreach ($request->defensive as $position => $value) {
                     if ($value === null) {
                        continue; // Skip this entry if the value is null
                    }
                    PlayTargetDefensivePlayer::create([
                        'play_id' => $target->id,
                        'defensive_position_id' => $position,
                        'strength' => $value,
                    ]);
                }
            }

            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play updated successfully", $target);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->playWriteFailedResponse($e);
        }
    }

    /**
     * Copy-on-write: the first time $leagueId's coach edits a Global Play,
     * clone it (including its offensive/defensive target rows) into a real
     * league-owned play, record the override, and return the clone. A second
     * edit from the same league finds the existing override and just returns
     * the already-owned clone - no second clone, no second override row
     * (updateOrCreate against the table's unique(league_id, global_play_id)
     * constraint handles the common case; a true simultaneous double-click
     * race is a known, accepted minor edge case for v1).
     */
    private function cloneGlobalPlayForLeague(Play $global, int $leagueId, $user): Play
    {
        $existing = LeaguePlayOverride::where([
            'league_id' => $leagueId, 'global_play_id' => $global->id, 'status' => 'customized',
        ])->first();
        if ($existing && $existing->customized_play_id) {
            return Play::findOrFail($existing->customized_play_id);
        }

        $clone = $global->replicate(['created_at', 'updated_at']);
        $clone->league_id = $leagueId;
        $clone->is_global = false;
        $clone->created_by_user_id = $user->id;
        $clone->created_by = null;
        $clone->save();

        foreach ($global->targetOffensivePlayers as $row) {
            PlayTargetOffensivePlayer::create([
                'play_id' => $clone->id,
                'offensive_position_id' => $row->offensive_position_id,
                'strength' => $row->strength,
            ]);
        }
        foreach (PlayTargetDefensivePlayer::where('play_id', $global->id)->get() as $row) {
            PlayTargetDefensivePlayer::create([
                'play_id' => $clone->id,
                'defensive_position_id' => $row->defensive_position_id,
                'strength' => $row->strength,
            ]);
        }

        LeaguePlayOverride::updateOrCreate(
            ['league_id' => $leagueId, 'global_play_id' => $global->id],
            ['status' => 'customized', 'customized_play_id' => $clone->id, 'created_by_user_id' => $user->id],
        );

        return $clone;
    }
    public function editPlay($id)
    {
        $play = Play::with(['offensivePositions','deffensivePositions'])->find($id);
        if ($play)
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play List", $play);

    }

    public function delete(Request $request, $id)
    {
        $play = Play::findOrFail($id);
        $user = auth()->user();
        $authz = app(\App\Services\PlayAuthorizationService::class);

        $leagueId = $play->is_global ? (int) $request->league_id : (int) $play->league_id;

        if ($play->is_global && !$leagueId) {
            return response()->json(['message' => 'league_id is required to hide a Global Play.'], 422);
        }

        if (!$authz->canDelete($user, $play, $leagueId ?: null)) {
            return response()->json(['message' => 'You are not allowed to delete this play.'], 403);
        }

        if ($play->is_global) {
            \App\Models\LeaguePlayOverride::updateOrCreate(
                ['league_id' => $leagueId, 'global_play_id' => $play->id],
                ['status' => 'hidden', 'customized_play_id' => null, 'created_by_user_id' => $user->id],
            );
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, 'Play removed from this league\'s playbook');
        }

        $play->delete();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, 'Play Delete Successfully ');
    }

    public function restore(Request $request, $id)
    {
        $play = Play::findOrFail($id);
        $user = auth()->user();
        $leagueId = (int) $request->league_id;
        $authz = app(\App\Services\PlayAuthorizationService::class);

        if (!$play->is_global || !$leagueId || !$authz->canRestore($user, $leagueId)) {
            return response()->json(['message' => 'You are not allowed to restore this play.'], 403);
        }

        \App\Models\LeaguePlayOverride::where([
            'league_id' => $leagueId, 'global_play_id' => $play->id, 'status' => 'hidden',
        ])->delete();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, 'Play restored to this league\'s playbook');
    }

    public function hiddenGlobalPlays(Request $request)
    {
        $user = auth()->user();
        $leagueId = (int) $request->league_id;
        $authz = app(\App\Services\PlayAuthorizationService::class);

        if (!$leagueId || !$authz->userCanActInLeague($user, $leagueId)) {
            return response()->json(['message' => 'You are not allowed to view this league.'], 403);
        }

        $hiddenIds = \App\Models\LeaguePlayOverride::where(['league_id' => $leagueId, 'status' => 'hidden'])
            ->pluck('global_play_id');

        $plays = Play::whereIn('id', $hiddenIds)->get();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, 'Hidden global plays', $plays);
    }

     public function getOffensivePositions()
    {
        $positions = OffensivePosition::all(['id', 'name']);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Offensive positions retrieved successfully.", $positions);
    }

    public function getDefensivePositions()
    {
        $positions = DefensivePosition::all(['id', 'name']);
         return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Defensive positions retrieved successfully.", $positions);
    }

    public function addPlayResult(Request $request)
    {

        $playResult = PlayResult::create([
            'game_id' => $request->game_id,
            'play_id' => $request->play_id,
            'type' => $request->type,
            'weather' => strtolower($request->weather) === 'normal' ? 'none' : strtolower($request->weather),
            'is_practice' => $request->is_practice,
            'result' => $request->result,
            'suggested_count' => $request->suggested_count ?? 0,
            'yardage_difference'=>$request->yardage_difference
        ]);

        // Same channels as PlaySuggested — lets the mobile app close its
        // "waiting for coach" popup as soon as the result form is submitted.
        $user = auth()->user();
        if ($user) {
            $coachGroupId = $user->role === 'head_coach' ? $user->id : $user->head_coach_id;
            $leagueId = BroadcastLeagueResolver::fromRequest($request);

            if ($coachGroupId && $leagueId !== null) {
                broadcast(new PlayResultSubmitted($playResult, $coachGroupId, $leagueId))->toOthers();
            } else {
                Log::warning('PlayResultSubmitted skipped: league_id or coach_group_id could not be resolved', [
                    'coach_group_id' => $coachGroupId,
                    'game_id' => $request->game_id,
                ]);
            }
        }

        // Optional: the yardage-modal Save step used to fire two more requests
        // right after this one - the QB "endplay" broadcast and the live
        // scoreboard "INFO" broadcast - always paired with saving the result.
        // Reusing the exact same controller methods here (same validation, same
        // payload shape) instead of duplicating their logic, so one request
        // covers what used to take three, with identical behavior. Absent
        // fields mean a plain result save, unaffected.
        if ($request->filled('qb_broadcast')) {
            try {
                $qbRequest = \Illuminate\Http\Request::create('', 'POST', $request->input('qb_broadcast'));
                app(BroadCastScoreController::class)->scoreBoardBroadCastQB($qbRequest);
            } catch (\Throwable $e) {
                Log::error('scoreBoardBroadCastQB (via play-results-add) failed: ' . $e->getMessage());
            }
        }

        if ($request->filled('scoreboard_broadcast')) {
            try {
                $sbData = $request->input('scoreboard_broadcast');
                $sbRequest = \Illuminate\Http\Request::create('', 'POST', $sbData);
                if ($request->boolean('is_practice')) {
                    app(BroadCastScoreController::class)->practiceScoreBoardBroadCast($sbRequest);
                } else {
                    app(BroadCastScoreController::class)->scoreBoardBroadCast($sbRequest);
                }
            } catch (\Throwable $e) {
                Log::error('scoreboard broadcast (via play-results-add) failed: ' . $e->getMessage());
            }
        }

        // Optional: the same Save also used to fire matchEvents() separately -
        // a plain "position changed" log entry (add-play-game-log) with no
        // broadcast of its own - and the result-confirm step right after this
        // one fired its own add-play-game-log too. Both log entries are real,
        // separate rows the Events tab reads back (PositionNumber vs the
        // Successful/Failed/point entry), so log_data accepts either a single
        // object (one row) or an array of them (one row each), and this fires
        // addPointsObject once per entry - same reuse pattern, still one request.
        if ($request->filled('log_data')) {
            $logEntries = $request->input('log_data');
            $logEntries = array_is_list($logEntries) ? $logEntries : [$logEntries];
            foreach ($logEntries as $logEntry) {
                try {
                    $logRequest = \Illuminate\Http\Request::create('', 'POST', $logEntry);
                    app(PlayGameModeController::class)->addPointsObject($logRequest);
                } catch (\Throwable $e) {
                    Log::error('addPointsObject (via play-results-add) failed: ' . $e->getMessage());
                }
            }
        }

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "suggestion plays wining ratio is added", $playResult);
    }
        public function getPlayResult(Request $request)
        {


            $gameId = $request->game_id;
            $playId = $request->play_id;
            $type = $request->type;
            $is_practice = $request->is_practice;


            // You might want to validate these IDs before querying (optional)

            $playResult = PlayResult::where('play_id', $playId)
                                    ->where('type', $type)
                                    ->get();

            if (!$playResult) {
                return response()->json([
                    'message' => 'Play result not found'
                ], 404);
            }

           return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Plays Suggestion is Fetch", $playResult);
        }



public function playOffenseTargetStore(Request $request)
{
    $data = $request->validate([
        'play_id' => 'required|integer|exists:plays,id',
        'strengths' => 'required|array',
        'strengths.*.target_offensive_id' => 'required|integer|exists:offensive_positions,id',
        'strengths.*.code' => 'required|string',
        'strengths.*.strength' => 'required|integer',
        'strengths.*.defensive_plays' => 'required|array|min:1',
        'strengths.*.defensive_plays.*.target_defensive_id' => 'required|integer|exists:defensive_positions,id',
        'strengths.*.defensive_plays.*.strength' => 'required|integer|min:0|max:100',
    ]);

    try {
        DB::transaction(function () use ($data) {
            // Delete existing records for this play_id before inserting updated ones
            OffensiveTargetStrength::where('play_id', $data['play_id'])->delete();

            foreach ($data['strengths'] as $offensiveItem) {
                foreach ($offensiveItem['defensive_plays'] as $defPlay) {
                    OffensiveTargetStrength::create([
                        'play_id' => $data['play_id'],
                        'target_offensive_id' => $offensiveItem['target_offensive_id'],
                        'code' => $offensiveItem['code'],
                        'target_defensive_id' => $defPlay['target_defensive_id'],
                        'strength' => $offensiveItem['strength'],
                        'total_strength' => $defPlay['strength'], // optional or remove this field if unused
                    ]);
                }
            }
        });

        return response()->json(['message' => 'Offensive strengths saved successfully.']);
    } catch (\Exception $e) {
        \Log::error('Failed to save offensive strengths: ' . $e->getMessage());
        return response()->json(['message' => 'Failed to save offensive strengths.'], 500);
    }
}
    public function getByPlayId($playId)
    {
        $records = OffensiveTargetStrength::with(['offensivePosition', 'defensivePosition'])
            ->where('play_id', $playId)
            ->get();
        return response()->json($records);
    }
     public function getOffensiveTargetsByPlay($playId)
    {
        $records = OffensiveTargetStrength::with(['offensivePosition', 'defensivePosition'])
            ->where('play_id', $playId)
            ->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "get target", $records);

    }
}
