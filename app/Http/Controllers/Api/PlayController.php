<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\Play;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class PlayController extends Controller
{

    public function index(Request $request)
    {
       $id =  ['1',$request->league_id];
       $play =  Play::whereIn('league_id',$id)->get();
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
            'preferred_down' => 'required|integer|in:1,2,3,4',
            'possession' => 'required|string|in:offensive,defensive',
        ]);
 
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
        $play->preferred_down = $request->preferred_down;
        $play->possession = $request->possession;
        $play->description = $request->description;
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
 
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play Uploaded Successfully", $play);
    }

    public function delete(Request $request)
    {
       $play = Play::find($request->id);
       if($play)
       $play->delete();
       return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play Delete Successfully ");
    }
}
