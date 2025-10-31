<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\Play;
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


class PlayController extends Controller
{

    public function index(Request $request)
    {  
    
        $userRoleIds = auth()->user()->roles->pluck('id');
         $id =  ['1', $request->league_id];
        // $play =  Play::whereIn('league_id', $id)->get();
        $play = Play::with(['roles', 'playResults'])
    ->where(function ($query) use ($id, $userRoleIds) {
        $query->orWhereIn('league_id', $id)
            ->orWhereHas('roles', function ($q) use ($userRoleIds) {
                $q->whereIn('roleables.role_id', $userRoleIds);
            });
    })
    ->withCount([
        'playResults as win_result' => function ($q) {
            $q->where('result', 'win');
        },
        'playResults as loss_result' => function ($q) {
            $q->where('result', 'loss');
        },
         'playResults as total_count'
    ])
     ->withAvg('playResults as yardage_difference', 'yardage_difference') 
    ->get();


      
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play Uploaded List ", $play);
    }
    // comment By Noor
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp',
    //         'video' => 'nullable|file|mimes:mp4,mov,avi,wmv',
    //         'play_name' => 'required|string',
    //         'league_id' => 'required|exists:leagues,id',
    //         'play_type' => 'required|integer',
    //         'zone_selection' => 'required|integer',
    //         'min_expected_yard' => 'required|string',
    //         'max_expected_yard' => 'required|string',
    //         'target_offensive' => 'required|integer',
    //         'opposing_defensive' => 'required|integer',
    //         'pre_snap_motion' => 'required|integer',
    //         'play_action_fake' => 'required|integer',
    //         'preferred_down' => 'required|integer|in:1,2,3,4',
    //         'possession' => 'required|string|in:offensive,defensive',
    //     ]);

    //     $play = new Play();
    //     $play->play_name = $request->play_name;
    //     $play->league_id = $request->league_id;
    //     $play->play_type = $request->play_type;
    //     $play->zone_selection = $request->zone_selection;
    //     $play->min_expected_yard = $request->min_expected_yard;
    //     $play->max_expected_yard = $request->max_expected_yard;
    //     $play->target_offensive = $request->target_offensive;
    //     $play->opposing_defensive = $request->opposing_defensive;
    //     $play->pre_snap_motion = $request->pre_snap_motion;
    //     $play->play_action_fake = $request->play_action_fake;
    //     $play->preferred_down = $request->preferred_down;
    //     $play->possession = $request->possession;

    //     // Upload image
    //     if ($request->hasFile('image')) {
    //         $imagePath = uploadImage($request->file('image'), 'public/uploads/public');
    //         $play->image = $imagePath;
    //     }

    //     // Upload video (optional)
    //     if ($request->hasFile('video')) {
    //         $videoPath = uploadImage($request->file('video'), 'public/uploads/videos');
    //         $play->video_path = $videoPath;
    //     }

    //     $play->save();

    //     return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play Uploaded Successfully", $play);
    // }

    public function store(Request $request)
    {
         
       
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp',
           
            'play_name' => 'required|string',
            'playType' => 'required|string',
            'league_id' => 'required|exists:leagues,id',
            'play_type' => 'required|integer',
            'zone_selection' => 'required|integer',
            'min_expected_yard' => 'required|string',
            'max_expected_yard' => 'required|string',
            'target_offensive' => 'required|integer',
            'opposing_defensive' => 'required|integer',
            'pre_snap_motion' => 'required|integer',
            'play_action_fake' => 'required|integer',
            // 'preferred_down' => 'required|in:1,2,3,4',
            'possession' => 'required|string|in:offensive,defensive',
        ]);
        DB::beginTransaction();

        try {
            $play = new Play();
            $play->offensive_play_type=$request->playType;
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
            $play->position_status = 2;
            $play->video_path = 'video path';
            // Upload image
           if ($request->hasFile('image')) {
    $imageFile = $request->file('image');

    \Log::info('Image uploaded', [
        'original_name' => $imageFile->getClientOriginalName(),
        'mime_type'     => $imageFile->getClientMimeType(),
        'size'          => $imageFile->getSize(),
    ]);

    $imagePath = uploadImage($imageFile, 'public/uploads/public');
    $play->image = $imagePath;
}

// Replace video if uploaded
if ($request->hasFile('video')) {
    $videoFile = $request->file('video');

    \Log::info('Video uploaded', [
        'original_name' => $videoFile->getClientOriginalName(),
        'mime_type'     => $videoFile->getClientMimeType(),
        'size'          => $videoFile->getSize(),
    ]);

    $videoPath = uploadImage($videoFile, 'public/uploads/videos');
    $play->video_path = $videoPath;
}
            $play->save();
            
           
             
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
        } catch (\Throwable $e) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $e->getMessage());
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


    public function update(Request $request, $id)
    {
        $request->validate([
           
            'play_name' => 'required|string',
            'league_id' => 'required|exists:leagues,id',
            'play_type' => 'required',
            'playType' => 'required|string',
            'zone_selection' => 'required|integer',
            'min_expected_yard' => 'required|string',
            'max_expected_yard' => 'required|string',
            'target_offensive' => 'required|integer',
            'opposing_defensive' => 'required|integer',
            'pre_snap_motion' => 'required|integer',
            'play_action_fake' => 'required|integer',
            'possession' => 'required|string|in:offensive,defensive',
        ]);
     
      
        DB::beginTransaction();

        try {
            $play = Play::findOrFail($id);

            $play->play_name = $request->play_name;
            $play->league_id = $request->league_id;
            $play->play_type = $request->play_type;
            $play->offensive_play_type=$request->playType;
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

            // Replace image if uploaded
            if ($request->hasFile('image')) {
              
                $imagePath = uploadImage($request->file('image'), 'public/uploads/public');
                $play->image = $imagePath;
            }

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
        } catch (\Throwable $e) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $e->getMessage());
        }
    }
    public function editPlay($id)
    {
        $play = Play::with(['offensivePositions','deffensivePositions'])->find($id);
        Log::info($play);
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
}
