<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\LeagueTeam;
use App\Models\Team;
use App\Models\TeamPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $team =  new Team();
            $team->name =  $request->team_name;
            if ($request->hasFile('image')) {
                $path =  uploadImage($request->image, 'uploads');
                $team->image = $path;
            }
            $team->save();
           

            
            foreach ($request->playerid as  $key=> $id) {
                $t_player =  new TeamPlayer();
                $t_player->team_id = $team->id;
                $t_player->player_id = $id;
                $t_player->type = $request->playertype[$key];
                $t_player->save();
            }
            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team save successFully", $team);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
        }
    }

    public function index()
    {
        $team =  Team::with('teamplayer')->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team list", $team);
    }
    public function view($id)
    {
        $team =  LeagueTeam::find($id);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team view", $team);
    }


    public function update(Request $request ,$id)
    {
        DB::beginTransaction();
        try {

            $team = LeagueTeam::find($id);
            $team->team_name =  $request->team_name;
            if ($request->hasFile('image')) {
                $path =  uploadImage($request->image, 'uploads');
                $team->image = $path;
            }
            $team->save();
            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team update successFully", $team);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
        }
    }

    public  function teamListByLeague(Request $request)
    {
        $team = LeagueTeam::where('league_id',$request->id)->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team List", $team);
    }
}
