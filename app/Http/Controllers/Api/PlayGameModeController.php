<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\PlayGameLog;
use App\Models\PlayGameMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlayGameModeController extends Controller
{
    public function startGameGode(Request $request)
    {
        DB::beginTransaction();
        try {
            $game = new PlayGameMode();
            $game->sport_id =$request->sport_id;
            $game->league_id = $request->league_id;
            $game->my_team_id =$request->my_team_id;
            $game->oponent_team_id =$request->oponent_team_id;
            $game->score =0;
            $game->quater = '';
            $game->downs ='';
            $game->status = 0;
            $game->save();
            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Game Start SuccessFully ", $game);
        } catch (\Throwable $th) {
           DB::rollBack();
            return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());

        }
    }

    public function addPoints(Request $request)
    {
        try {

                $game =  PlayGameMode::find($request->game_id);
                $game->score = $game->score+$request->my_points;
                $game->save();


                $game_log= new PlayGameLog();
                $game_log->game_id =  $request->game_id;
                $game_log->sport_id=  $request->sport_id;
                $game_log->league_id=  $request->league_id;
                $game_log->player_id=  $request->player_id;
                $game_log->my_team_id=  $request->my_team_id;
                $game_log->oponent_team_id=  $request->oponent_team_id;
                $game_log->quater=  $request->quater;
                $game_log->downs=  $request->downs;
                $game_log->current_position=  $request->current_position;
                $game_log->target=  $request->target;
                $game_log->my_points=  $request->my_points;
                $game_log->oponent_points=  $request->oponent_points;
                $game_log->time=  $request->time;
                $game_log->type_of_log=  $request->type_of_log;
                $game_log->save();


                DB::commit();
             return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Update Changes Added ", $game);
        } catch (\Throwable $th) {
             DB::rollBack();
            return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
        }
    }
}
