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
    public function store(Request $request)
    {
        $play =  new Play();
        $request->validate([
            'zip_file' => 'required|file|mimes:zip',
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

        $zip = $request->file('zip_file');
        $zipPath = $zip->storeAs('temp', $zip->getClientOriginalName());

        $zipFilePath = storage_path('app/' . $zipPath);
        $extractPath = storage_path('app/temp/' . Str::random(10));
        $zipArchive = new ZipArchive;
        if ($zipArchive->open($zipFilePath) === TRUE) {
            $zipArchive->extractTo($extractPath);
            $zipArchive->close();

            // Find the video file (assuming it's MP4)
            $videoFile = collect(scandir($extractPath))->first(fn($file) => Str::endsWith($file, ['.mp4', '.avi', '.mov']));

            if ($videoFile) {
                $videoPath = 'uploads/videos/' . $videoFile;
                Storage::disk('public')->put($videoPath, file_get_contents($extractPath . '/' . $videoFile));

                // Save to database
                $play = new Play();
                $play->play_name = $request->play_name;
                $play->play_type = $request->play_type;
                $play->zone_selection = $request->zone_selection;
                $play->min_expected_yard = $request->min_expected_yard;
                $play->max_expected_yard = $request->max_expected_yard;
                $play->target_offensive = $request->target_offensive;
                $play->opposing_defensive = $request->opposing_defensive;
                $play->pre_snap_motion = $request->pre_snap_motion;
                $play->play_action_fake = $request->play_action_fake;
                $play->video_path = $videoPath;
                $play->save();
            }

            // Cleanup temp files
            unlink($zipFilePath);
            Storage::deleteDirectory('temp');
        }
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Play Uploaded Successfully ", $play);

    }
}
