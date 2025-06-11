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


class PlayController extends Controller
{

    public function index(Request $request)
    {
        $id =  ['1', $request->league_id];
        $play =  Play::whereIn('league_id', $id)->get();
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
            'video' => 'nullable|file|mimes:mp4,mov,avi,wmv',
            'play_name' => 'required|string',
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
            $play->play_name = $request->play_name;
            $play->league_id = $request->league_id;
            $play->play_type = $request->play_type;
            $play->zone_selection = $request->zone_selection;
            $play->min_expected_yard = $request->min_expected_yard;
            $play->max_expected_yard = $request->max_expected_yard;
            // $play->target_offensive = $request->target_offensive;
            // $play->opposing_defensive = $request->opposing_defensive;
            $play->pre_snap_motion = $request->pre_snap_motion;
            $play->play_action_fake = $request->play_action_fake;
            \Log::info(['preferred_down' => $request->preferred_down]);
            if (is_array($request->preferred_down)) {
                $play->preferred_down = implode(',', $request->preferred_down);
            } else {
                // If it's a single value or null, just save it directly
                $play->preferred_down = $request->preferred_down;
            }

            $play->possession = $request->possession;
            $play->description = $request->description;
            $play->position_status = 2;
            $play->video_path = 'video path';
            // Upload image
            if ($request->hasFile('image')) {
                $imagePath = uploadImage($request->file('image'), 'public/uploads/public');
                $play->image = $imagePath;
            }
            // Upload video (optional)
            if ($request->hasFile('video')) {
                $videoPath = uploadImage($request->file('video'), 'public/uploads/videos');
                $play->video_path = $videoPath;
            }
            $play->save();

            if ($request->possession === 'offensive' && is_array($request->offensive)) {
                $offensivePositions = OffensivePosition::pluck('id', 'name')->toArray();
                foreach ($request->offensive as $position => $value) {
                    \Log::info(['data' => $position]);
                    // if (!isset($offensivePositions[$position])) continue;
                    PlayTargetOffensivePlayer::create([
                        'play_id' => $play->id,
                        'offensive_position_id' => $position,
                        'strength' => $value, // or other columns if needed
                    ]);
                }
            }

            // Handle Defensive Position Data
            if ($request->possession === 'defensive' && is_array($request->defensive)) {
                $defensivePositions = DefensivePosition::pluck('id', 'name')->toArray();
                \Log::info(['data' => $defensivePositions]);
                foreach ($request->defensive as $position => $value) {
                    // if (!isset($defensivePositions[$position])) continue;

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
}
