<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\League;
use App\Models\LeagueRule;
use App\Models\LeagueTeam;
use App\Models\Player;
use App\Models\Sport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SportController extends Controller
{
    public function sport(Request $request){
        $sport = Sport::all();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "sport", $sport);
    }

    public function league(Request $request)
    {
        $id =  auth()->user()->id;
        $league = League::with([
            'teams',  // Selecting only 'id' and 'name' from teams
            'league_rule:id,title', // Selecting only 'id' and 'title' from leaque_rule
            'sport:id,title' // Selecting only 'id' and 'title' from sport
        ])->where('user_id',auth('api')->user()->id)->where('sport_id',$request->sport_id)->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "leauqe List  ", $league);
    }
    public function leagueRule(Request $request)
    {
        $League = LeagueRule::all();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "League Rule ", $League);
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
           foreach($request->team_name as $value)
           {
             $team =  new LeagueTeam;
             $team->league_id =  $League->id;
             $team->team_name = $value;
             $team->save();
           }
           DB::commit();
           return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "League Created SuccessFully",$League);
        } catch (\Throwable $th) {
          DB::rollBack();
          return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
        }
    }

 

    public function leagueView(Request $request)
    {
      $leauqe =   League::with('teams','league_rule','sport')->find($request->id);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "leauqe List  ", $leauqe);
    }
    public function leagueUpdate(Request $request)
    {
        DB::beginTransaction();
        try {

           $League =  League::find($request->id);
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
           LeagueTeam::where('league_id',$League->id)->delete();
           foreach($request->team_name as $value)
           {
             $team =  new LeagueTeam;
             $team->league_id =  $League->id;
             $team->team_name = $value;
             $team->save();
           }
           DB::commit();
           return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "League Updated  SuccessFully ", $League);
        } catch (\Throwable $th) {
          DB::rollBack();
          return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
        }
    }
    public function dashboard(Request $request)
    {
        $leauqe =  League::with('teams','league_rule','sport')->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "leauqe List  ", $leauqe);
    }

 
}
