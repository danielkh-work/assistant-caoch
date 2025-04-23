<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\ConfiguredPlayingTeamPlayer;
use App\Models\ConfigureFormation;
use Illuminate\Support\Facades\DB;

class ConfigureController extends Controller
{
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {

            $types = collect($request->type)->unique();

          
            ConfiguredPlayingTeamPlayer::where('team_id', $request->team_id)
                ->whereIn('type', $types)
                ->delete();
    
         
            foreach ($request->player_id as $index => $playerId) {
                ConfiguredPlayingTeamPlayer::updateOrCreate(
                    [
                        'team_id' => $request->team_id,
                        'player_id' => $playerId,
                        'type' => $request->type[$index],
                        'team_type'=>1
                    ],
                    [] 
                );
            }
          
           DB::commit();
           return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure Player successFully");
        } catch (\Throwable $th) {
          DB::rollBack();
          return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
        }
    }
    public function storevisiting(Request $request)
    {
        DB::beginTransaction();
        try {
            $types = collect($request->type)->unique();
            ConfiguredPlayingTeamPlayer::where('team_id', $request->team_id)
                ->whereIn('type', $types)
                ->delete();
            foreach ($request->player_id as $index => $playerId) {
                ConfiguredPlayingTeamPlayer::updateOrCreate(
                    [
                        'team_id' => $request->team_id,
                        'player_id' => $playerId,
                        'type' => $request->type[$index],
                        'team_type'=>2
                    ],
                    [] 
                );
            }
           DB::commit();
           return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure Player successFully");
        } catch (\Throwable $th) {
          DB::rollBack();
          return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
        }
    }
    public function view(Request $request)
    {
        $configure =  ConfiguredPlayingTeamPlayer::with('player')->where('team_id',$request->team_id)->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure Player List",$configure);
    }

    public function configureFormation(Request $request)
    {
        DB::beginTransaction();
        try {
            $configureFormation =  new ConfigureFormation();
            $configureFormation->user_id = auth()->user()->id;
            $configureFormation->formation_id = $request->formation_id;
            $configureFormation->league_id =  $request->league_id;
            $configureFormation->save();

            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure formation successFully");
        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
        }
    }
    public function configureFormationView(Request $request)
    {
        $configure =  ConfigureFormation::with('league','team')->where('league_id',$request->league_id)->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure formation List",$configure);
  
    }
}
