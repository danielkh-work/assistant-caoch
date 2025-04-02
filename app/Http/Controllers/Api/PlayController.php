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
       $play =  Play::all();
       return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play Uploaded List ", $play);
    }
    public function store(Request $request)
    {
        $play =  new Play();
        $request->validate([
            'image' => 'required',
            'play_name' => 'required|string',
            'play_type' => 'required|integer',
            'zone_selection' => 'required|integer',
            'min_expected_yard' => 'required|string',
            'max_expected_yard' => 'required|string',
            'target_offensive' => 'required|integer',
            'opposing_defensive' => 'required|integer',
            'pre_snap_motion' => 'required|integer',
            'play_action_fake' => 'required|integer',
        ]);
        $play = new Play();
        $play->play_name = $request->play_name;
        $play->play_type = $request->play_type;
        $play->zone_selection = $request->zone_selection;
        $play->min_expected_yard = $request->min_expected_yard;
        $play->max_expected_yard = $request->max_expected_yard;
        $play->target_offensive = $request->target_offensive;

                if($request->hasFile('image'))
                {
                    $path =   uploadImage($request->image,'public');
                    $play->video_path = $path;
                }

                $play->opposing_defensive = $request->opposing_defensive;
                $play->pre_snap_motion = $request->pre_snap_motion;
                $play->play_action_fake = $request->play_action_fake;
                $play->type = $request->type;

                $play->perfer_down_selection =  $request->perfer_down_selection;
                $play->save();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play Uploaded Successfully ", $play);

    }
}
