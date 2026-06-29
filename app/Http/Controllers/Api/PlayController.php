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
use App\Models\OffensivePosition;
use App\Models\DefensivePosition;
use App\Models\PlayResult;
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
        $userRoleIds = auth()->user()->roles->pluck('id');
        $id = ['1', $request->league_id];

        $query = Play::with(['roles', 'playResults', 'offensiveTargets'])
            ->where(function ($sub) use ($id, $userRoleIds) {
                $sub->orWhereIn('league_id', $id)
                    ->orWhereHas('roles', function ($q) use ($userRoleIds) {
                        $q->whereIn('roleables.role_id', $userRoleIds);
                    });
            })
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
            ->withAvg('playResults as yardage_difference', 'yardage_difference')
            ->orderByDesc('win_result');

        $searchTerm = trim((string) $request->input('search', ''));
        if ($searchTerm !== '') {
            $needle = '%' . addcslashes($searchTerm, '%_\\') . '%';
            $query->where('play_name', 'like', $needle);
        }

        $paginateRequested = $request->has('page')
            || $request->has('per_page')
            || $request->filled('search');

        if ($paginateRequested) {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = max(1, min(100, (int) $request->input('per_page', 4)));

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
        $this->normalizePlayTypeRequest($request);
        $request->validate($this->playUpdateValidationRules());

        $play = Play::findOrFail($id);
        $this->validateHmarkImagesOnUpdate($request, $play);
     
      
        DB::beginTransaction();

        try {
            $play->play_name = $request->play_name;
            $play->league_id = $request->league_id;
            $play->play_type = $request->play_type;

            if ($request->filled('playType')) {
                $play->offensive_play_type = $request->playType;
            }

            $play->quarter = $request->quarter;
            $play->zone_selection = $request->zone_selection;
            $play->min_expected_yard = $request->min_expected_yard;
            $play->max_expected_yard = $request->max_expected_yard;
            $play->pre_snap_motion = $request->pre_snap_motion;
            $play->play_action_fake = $request->play_action_fake;

            $play->preferred_down = is_array($request->preferred_down)
                ? implode(',', $request->preferred_down)
                : $request->preferred_down;

            $play->strategies = is_array($request->strategies)
                ? implode(',', $request->strategies)
                : $request->strategies;

            $play->possession = $request->possession;
            $play->description = $request->description;

            if ($this->requestHasMeaningfulValue($request, 'read_2')) {
                $play->read_1 = $request->read_2;
            }

            if ($this->requestHasMeaningfulValue($request, 'read_3')) {
                $play->read_2 = $request->read_3;
            }

            $this->assignHmarkImagesFromRequest($request, $play, true);

            // Replace video if uploaded
            if ($request->hasFile('video')) {
                $videoPath = uploadImage($request->file('video'), 'public/uploads/videos');
                $play->video_path = $videoPath;
            }

            $play->save();
            

            // Delete old offensive links and recreate
            PlayTargetOffensivePlayer::where('play_id', $play->id)->delete();
            if (is_array($request->offensive)) {
                foreach ($request->offensive as $position => $value) {
                    if ($value === null) {
                        continue; // Skip this entry if the value is null
                    }
                    PlayTargetOffensivePlayer::create([
                        'play_id' => $play->id,
                        'offensive_position_id' => $position,
                        'strength' => $value,
                    ]);
                }
            }

            // Delete old defensive links and recreate
            PlayTargetDefensivePlayer::where('play_id', $play->id)->delete();
            if (is_array($request->defensive)) {
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
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play updated successfully", $play);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->playWriteFailedResponse($e);
        }
    }
    public function editPlay($id)
    {
        $play = Play::with(['offensivePositions','deffensivePositions'])->find($id);
        if ($play)
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play List", $play);
       
    }

    public function delete(Request $request)
    {
        $play = Play::find($request->id);
        if ($play)
            $play->delete();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play Delete Successfully ");
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
