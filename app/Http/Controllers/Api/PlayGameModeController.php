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
       
     $data = $request->all();

   if (empty($data) || !is_array($data)) {
                return response()->json(true); // or return true;
    }
    $logs = [];

   
    DB::beginTransaction();

    try {
      
        foreach ($data as $value) {
            // Update the game record
            $game = PlayGameMode::find($value['game_id']);
            $game->save();

            // Prepare the log data
            $logs[] = [
                'game_id' => $value['game_id'],
                'sport_id' => $value['sport_id'],
                'league_id' => $value['league_id'],
                'player_id' => $value['player_id'],
                'my_team_id' => $value['my_team_id'],
                'oponent_team_id' => $value['oponent_team_id'],
                'quater' => $value['quater'],
                'downs' => $value['downs'],
                'current_position' => $value['current_position'],
                'target' => $value['target'],
                'my_points' => $value['my_points'],
                'oponent_points' => $value['oponent_points'],
                'time' => $value['time'],
                'type_of_log' => $value['type_of_log'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert all logs in a single query
        PlayGameLog::insert($logs);

        // Commit the transaction
        DB::commit();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Update Changes Added", $game);
    } catch (\Throwable $th) {
        // Rollback the transaction in case of an error
        DB::rollBack();

        return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
    }

    }
}
