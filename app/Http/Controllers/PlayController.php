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
use Spatie\Permission\Models\Role;
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
        
        $query = Play::with('roles')->orderBy('id', 'desc');   
        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('roles.id', $request->role);
            });
        }
        $data = $query->get();
        if ($request->ajax()) {
            return DataTables::of($data)
                ->addIndexColumn()
                // ->addColumn('position',function ($row){
                //     return $row->is_verify==1 ? 'offence' : 'deffence';
                // })
                  ->addColumn('roles', function($row) {
                            return $row->roles->pluck('name')->implode(', ');
                    })
                    ->addColumn('action', function($row){
                    $editUrl = route('play.edit', ['id' => $row->id]);
                    $deleteUrl = route('play.destroy', ['id' => $row->id]);

                   

                    return '
                        <a href="' . $editUrl . '" class="btn btn-warning btn-sm me-1">Edit</a>
                       
                    ';
                    })
                    //  <form action="' . $deleteUrl . '" method="POST" style="display:inline;">
                    //         ' . csrf_field() . '
                    //         ' . method_field('DELETE') . '
                    //         <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure?\')">Delete</button>
                    //     </form>
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('plays.index',$data);
    }
    public function create(){

       $league = League::all();
       $offensive_position = OffensivePosition::all(['id', 'name']);
       $defensive_positions = DefensivePosition::all(['id', 'name']);
        $roles =  Role::all();
       return view('plays.create',compact('league','offensive_position','defensive_positions','roles'));
    }
    public function edit($id)
    {
        $play = Play::with(['offensivePositions','deffensivePositions'])->findOrFail($id);
        $league = League::all();
        $offensive_position = OffensivePosition::all(['id', 'name']);
        $defensive_positions = DefensivePosition::all(['id', 'name']);

        return view('plays.edit', compact('play', 'league', 'offensive_position', 'defensive_positions'));
    }

     public function update(Request $request, $id)
    {
       
 
        DB::beginTransaction();
      
            $play = Play::findOrFail($id);
            $play->play_name = $request->play_name;
            $play->league_id = $request->league_id;
            $play->min_expected_yard = $request->min_expected_yard;
            $play->preferred_down = is_array($request->preferred_down)
                ? implode(',', $request->preferred_down)
                : $request->preferred_down;
            $play->strategies = is_array($request->strategies)
                ? implode(',', $request->strategies)
                : $request->strategies;
            $play->possession = $request->possession;
            $play->description = $request->description;
            if ($request->hasFile('image')) {
                $imagePath = uploadImage($request->file('image'), 'public/uploads/public');
                $play->image = $imagePath;
            }
            if ($request->hasFile('video')) {
                $videoPath = uploadImage($request->file('video'), 'public/uploads/videos');
                $play->video_path = $videoPath;
            }

            $play->save();
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
            $play->roles()->sync($request->role_id); // assign to multiple roles
             DB::commit();
            return redirect()->route('play.index');
        // } catch (\Throwable $e) {
        //     DB::rollBack();
        //    dd($e->getMessage());
        // }
    }

    public function destroy($id)
    {
        $play = Play::findOrFail($id);
        $play->delete();
        return redirect()->route('play.index')->with('success', 'Play deleted successfully');
    }
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $play = new Play();
            $play->play_name = $request->play_name;
            $play->league_id = $request->league_id;
            $play->play_type = 1;
            $play->quarter = 1;
            $play->created_by = 'admin';
            $play->zone_selection = 1;
            $play->min_expected_yard = $request->min_expected_yard;
            $play->max_expected_yard = 1;
          
            $play->pre_snap_motion = 1;
            $play->play_action_fake = 1;
            
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

             if ($request->has('role_id') && is_array($request->role_id)) {
                 $play->roles()->sync($request->role_id);
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
           return redirect()->route('play.index');
        } catch (\Throwable $e) {
            DB::rollBack();
            dd($e->getMessage());
        }

    }
}
