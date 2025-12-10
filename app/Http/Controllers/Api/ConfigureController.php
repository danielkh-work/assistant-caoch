<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\ConfiguredPlayingTeamPlayer;
use App\Models\ConfigureFormation;
use App\Models\ConfigurePlay;
use App\Models\ConfigureDefensivePlay;
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
                ->where('match_id', $request->match_id)
                ->delete();
    
         
            foreach ($request->player_id as $index => $playerId) {
                ConfiguredPlayingTeamPlayer::updateOrCreate(
                    [
                        'team_id' => $request->team_id,
                        'match_id' => $request->match_id,
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
                ->whereIn('type', $types) ->where('match_id', $request->match_id)
                ->delete();
            foreach ($request->player_id as $index => $playerId) {
                ConfiguredPlayingTeamPlayer::updateOrCreate(
                    [
                        'team_id' => $request->team_id,
                        'match_id' => $request->match_id,
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
     

        // Base query (runs in all cases)
        $query = ConfiguredPlayingTeamPlayer::with('player.player')
        ->where('team_id', $request->team_id)
        ->where('match_id', $request->game_id);

        

        $configured = $query->get();

       
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure Player List",$configured);
    }

    public function configureFormation(Request $request)
    {
        DB::beginTransaction();
        try {
            ConfigureFormation::where(['user_id'=> auth()->user()->id,'league_id'=>$request->league_id])->delete();
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
    public function configurePlay(Request $request)
    {
        DB::beginTransaction();
        try {
            ConfigurePlay::where(['user_id'=> auth()->user()->id,'league_id'=>$request->league_id,'match_id'=>$request->matchId])->delete();
            foreach($request->play_id as $value)
            {

                $configureFormation =  new ConfigurePlay();
                $configureFormation->user_id = auth()->user()->id;
                $configureFormation->play_id = $value;
                $configureFormation->match_id = $request->matchId;
                $configureFormation->league_id =  $request->league_id;
                $configureFormation->save();
            }

            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure Play successFully");
        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
        }
    }

     public function configureDefensivePlay(Request $request)
    {
        DB::beginTransaction();
        try {
            ConfigureDefensivePlay::where(['user_id'=> auth()->user()->id,'league_id'=>$request->league_id,'game_id'=>$request->matchId])->delete();
            foreach($request->play_id as $value)
            {

                $configureFormation =  new ConfigureDefensivePlay();
                $configureFormation->user_id = auth()->user()->id;
                $configureFormation->play_id = $value;
                $configureFormation->game_id = $request->matchId;
                $configureFormation->league_id =  $request->league_id;
                $configureFormation->save();
            }

            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure Play successFully");
        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
        }
    }
    public function configurePlayView(Request $request)
    {
        $configure =  ConfigurePlay::with('league','play')->where('league_id',$request->league_id)->where('match_id',$request->matchId)->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure Play List",$configure);
  
    }
     public function configurePlayDefensiveView(Request $request)
    {
        $configure =  ConfigureDefensivePlay::with('league','play')->where('league_id',$request->league_id)->where('game_id',$request->matchId)->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure Play List",$configure);
  
    }
    
}
