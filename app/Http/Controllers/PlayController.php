<?php

namespace App\Http\Controllers;

use App\Models\DefensivePosition;
use App\Models\League;
use App\Models\OffensivePosition;
use App\Models\Play;
use App\Models\PlayTargetDefensivePlayer;
use App\Models\PlayTargetOffensivePlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class PlayController extends Controller
{
      /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
         $data = Play::orderBy('id', 'desc')->get();
        if ($request->ajax()) {
            return DataTables::of($data)
                ->addIndexColumn()
                // ->addColumn('position',function ($row){
                //     return $row->is_verify==1 ? 'offence' : 'deffence';
                // })
                ->addColumn('action', function($row){
                    return '<a href="' . route('play.show', ['id' => $row->id]) . '" class="edit btn btn-primary btn-sm">View</a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('plays.index',$data);
    }
    public function create(){

       $league = League::all();
        return view('plays.create',compact('league'));
    }

    public function store(Request $request)
    {
         DB::beginTransaction();

        try {
            $play = new Play();
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
                $imagePath = uploadImage($request->file('image'), 'public/uploads/public');
                $play->image = $imagePath;
            }
            // Upload video (optional)
            if ($request->hasFile('video')) {
                $videoPath = uploadImage($request->file('video'), 'public/uploads/videos');
                $play->video_path = $videoPath;
            }
            $play->save();
            
            Log::info(["offensive",$request->offensive]);
            Log::info(["defensive",$request->defensive]);
             
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
            return view('plays.create');
        } catch (\Throwable $e) {
            DB::rollBack();
            dd($e->getMessage());
        }

    }
}
