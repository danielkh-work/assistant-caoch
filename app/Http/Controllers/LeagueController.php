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
        $data = League::orderBy('id', 'desc')->get();
        if ($request->ajax()) {
            return DataTables::of($data)
                ->addIndexColumn()
                // ->addColumn('position',function ($row){
                //     return $row->is_verify==1 ? 'offence' : 'deffence';
                // })
                ->addColumn('action', function($row){
                    return '<a href="' . route('league.show', ['id' => $row->id]) . '" class="edit btn btn-primary btn-sm">View</a>';
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

    public function store(Request $request)
    {
      
        DB::beginTransaction();
        try {
 
           $League =  new League;
           $League->user_id=  auth('api')->user()->id;
           $League->sport_id=$request->sport_id;
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

    public function show($id)
    {
        $league =    League::find($id);
        return view('league.show',compact('league'));
    }
}
