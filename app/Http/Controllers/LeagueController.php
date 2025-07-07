<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueRule;
use App\Models\LeagueTeam;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\DataTables;
class LeagueController extends Controller
{
       /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        
        $query = League::with('teams','roles')->orderBy('id', 'desc');   
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
                    $editUrl = route('league.edit', ['id' => $row->id]);
                    $deleteUrl = route('play.destroy', ['id' => $row->id]);

                    return '
                        <a href="' . $editUrl . '" class="btn btn-warning btn-sm me-1">Edit</a>
                       
                    ';
                    //  <form action="' . $deleteUrl . '" method="POST" style="display:inline;">
                    //         ' . csrf_field() . '
                    //         ' . method_field('DELETE') . '
                    //         <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure?\')">Delete</button>
                    //     </form>
                    })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('league.index',$data);
    }

    public function create(){
        $league_rule =  LeagueRule::all();
        $sports =  Sport::all();
        // $teams =  Team::all();
        $roles =  Role::all();
        return view('league.create',compact('league_rule','sports','roles'));
    }
     public function destroy($id)
    {
        $play = League::findOrFail($id);
        $play->delete();
        return redirect()->route('league.index')->with('success', 'Play deleted successfully');
    }
    public function edit($id)
    {
        $league = League::with('roles')->findOrFail($id);
        $roles = Role::all();
        $league_rule = LeagueRule::all();
        $sports = Sport::all();
       
        return view('league.edit', compact('league', 'roles', 'league_rule', 'sports'));
    }

    public function store(Request $request)
    {
      
        DB::beginTransaction();
        try {
 
           $League =  new League;
           $League->user_id=  auth('api')->user()->id;
           $League->sport_id = 1;
           $League->league_rule_id=$request->league_rule_id;
           $League->number_of_team=$request->number_of_team;
           $League->title=$request->title;
           $League->number_of_downs=$request->number_of_downs;
           $League->length_of_field=$request->length_of_field;
           $League->number_of_timeouts=$request->number_of_timeouts;
           $League->clock_time=$request->clock_time;
           $League->number_of_quarters=$request->number_of_quarters;
           $League->length_of_quarters=$request->length_of_quarters;
           $League->stop_time_reason=$request->stop_time_reason;
           $League->overtime_rules=$request->overtime_rules;
           $League->number_of_players=$request->number_of_players;
           $League->flag_tbd =$request->flag_tbd;
           $League->save();
           foreach($request->team_name as $index => $value)
           {
             $team =  new LeagueTeam();
             $team->league_id =  $League->id;
             $team->team_name = $value;
             $team->type = $index == 0 ? 1 : null;
             $team->save();
           }
           
           DB::commit();
        
           $League->roles()->sync($request->role_id); // assign to multiple roles
           DB::commit();
             return  redirect()->route('league.index');
        } catch (\Throwable $th) {
          DB::rollBack();
          dd($th);
        }
    }

        public function update(Request $request,$id)
    {
        
        DB::beginTransaction();
        try {
            $League = League::findOrFail($id);
            $League->sport_id = 1;
            $League->league_rule_id = $request->league_rule_id;
            $League->number_of_team = $request->number_of_team;
            $League->title = $request->title;
            $League->number_of_downs = $request->number_of_downs;
            $League->length_of_field = $request->length_of_field;
            $League->number_of_timeouts = $request->number_of_timeouts;
            $League->clock_time = $request->clock_time;
            $League->number_of_quarters = $request->number_of_quarters;
            $League->length_of_quarters = $request->length_of_quarters;
            $League->stop_time_reason = $request->stop_time_reason;
            $League->overtime_rules = $request->overtime_rules;
            $League->number_of_players = $request->number_of_players;
            $League->flag_tbd = $request->flag_tbd;
            $League->save();
            
            $League->roles()->sync($request->role_id);
            // Delete existing teams and recreate
            // LeagueTeam::where('league_id', $League->id)->delete();

            // foreach ($request->team_name as $index => $value) {
            //     $team = new LeagueTeam;
            //     $team->league_id = $League->id;
            //     $team->type = $index == 0 ? 1 : null;
            //     $team->team_name = $value;
            //     $team->save();
            // }

            DB::commit();
         
            // Redirect to league index with success message
            return redirect()->route('league.index')
                ->with('success', 'League updated successfully!');
        } catch (\Throwable $th) {
            DB::rollBack();

            // Redirect back with error message
            return redirect()->back()
                ->with('error', 'Failed to update league: ' . $th->getMessage())
                ->withInput();
        }
    }


    public function show($id)
    {
        $league =    League::find($id);
        return view('league.show',compact('league'));
    }
}
