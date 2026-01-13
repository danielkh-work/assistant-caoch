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
         $user = auth()->user(); 
       
        DB::beginTransaction();
        try {
            $game = new PlayGameMode();
            $game->sport_id =$user->sport_id;
            $game->league_id = $request->league_id;
            $game->my_team_id =$request->my_team_id;
            $game->oponent_team_id =$request->oponent_team_id;
            $game->user_id = auth()->id();
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
    

    public function addPointsObject(Request $request)
    {

        \Log::info(['data'=>$request->all()]);

        
        $value = $request->all();

        \Log::info(['value... log data'=>$value]);

        if (empty($value) || !isset($value['game_id'])) {
            return response()->json(['message' => 'Invalid data'], 400);
        }

        DB::beginTransaction();

        try {
        // Update game
        // $game = PlayGameMode::findOrFail($value['game_id']);
        // $game->save();

        // ✅ Create object & save
        $log = new PlayGameLog();
        $log->game_id = $value['game_id'];
        $log->sport_id = auth()->user()->sport_id;
        $log->league_id = $value['league_id'];

        // ✅ new columns
        $log->players = json_encode($value['players']) ?? null;          // array
        $log->confirmed = $value['is_confirmed'] ?? null;   // true / false / null
                        
        $log->my_team_id = $value['my_team_id'];
        $log->oponent_team_id = $value['oponent_team_id'];
        $log->quater = $value['quater'];
        $log->play_id = $value['play_id'];
        $log->downs = $value['downs'];
        $log->play_yardage_gain = isset($value['play_yardage_gain']) ? $value['play_yardage_gain'] : null;
        $log->weather_status = $value['weather_status'];
        $log->current_position = $value['current_position'];
        $log->target = $value['target'];
        $log->my_points = $value['my_points'];
        $log->oponent_points = $value['oponent_points'];
        $log->time = $value['time'];
        $log->reasons = $value['reasons'] ?? '';
        $log->type_of_log = $value['type_of_log'];

        $log->save(); // ✅ save() method

        DB::commit();

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            "Update Added",
            $log
        );

        } catch (\Throwable $th) {
            DB::rollBack();

            return new BaseResponse(
                STATUS_CODE_BADREQUEST,
                STATUS_CODE_BADREQUEST,
                $th->getMessage()
            );
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
            \Log::info(['game'=>$game]);
            \Log::info(['game_id'=>$value['game_id']]);
            $game->save();

            // Prepare the log data
            $logs[] = [
                'game_id' => $value['game_id'],
                'sport_id' => auth()->user()->sport_id,
                'league_id' => $value['league_id'],
                'player_id' => $value['player_id'],
                'my_team_id' => $value['my_team_id'],
                'oponent_team_id' => $value['oponent_team_id'],
                'quater' => $value['quater'],
                'downs' => $value['downs'],
                'weather_status' => $value['weather_status'],
                'current_position' => $value['current_position'],
                'target' => $value['target'],
                'my_points' => $value['my_points'],
                'oponent_points' => $value['oponent_points'],
                'time' => $value['time'],
                'reasons' => $value['reasons'] ?? '',
                'type_of_log' => $value['type_of_log'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        //  'players' => json_encode($value['players']), // array → json
        //     'confirmed' => $value['is_confirmed'],        // true / false

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
