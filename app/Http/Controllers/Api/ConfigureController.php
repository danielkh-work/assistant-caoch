<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\ConfiguredPlayingTeamPlayer;
use App\Models\ConfigureFormation;
use App\Models\ConfigurePlay;
use App\Models\ConfigureDefensivePlay;
use App\Models\PersionalGrouping;
use Illuminate\Support\Facades\DB;

class ConfigureController extends Controller
{
    public function store(Request $request)
    {
       
        DB::beginTransaction();
        try {
            $playerIds = $request->input('player_id', []);
            if (! is_array($playerIds)) {
                $playerIds = $playerIds !== null && $playerIds !== '' ? [$playerIds] : [];
            }

            $types = $request->input('type', []);
            if (! is_array($types)) {
                $types = $types !== null && $types !== '' ? [$types] : [];
            }

            // Replace the full roster for this match: if we only deleted rows matching
            // the types present in the request, omitted squads (e.g. all offensive
            // removed while saving defensive only) would never be cleared.
            ConfiguredPlayingTeamPlayer::where('team_id', $request->team_id)
                ->where('match_id', $request->match_id)
                ->where('game_type', $request->game_type)
                ->where('team_type', 1)
                ->delete();
    
         
            foreach ($playerIds as $index => $playerId) {
     

                    ConfiguredPlayingTeamPlayer::create([
                        'team_id' => $request->team_id,
                        'match_id' => $request->match_id,
                        'type' => $types[$index] ?? 'offensive',
                        'team_type' => 1,
                        'game_type' => $request->game_type,
                        'player_id' => $request->game_type == 1 ? $playerId : null,
                        'practice_player_id' => $request->game_type != 1 ? $playerId : null,
                    ],[] );
            }
          
           DB::commit();
           PersionalGrouping::pruneAllStaleRepairsAfterConfigureSave(
               (int) $request->team_id,
               (int) $request->match_id,
               (int) $request->game_type
           );
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
            $playerIds = $request->input('player_id', []);
            if (! is_array($playerIds)) {
                $playerIds = $playerIds !== null && $playerIds !== '' ? [$playerIds] : [];
            }

            $types = $request->input('type', []);
            if (! is_array($types)) {
                $types = $types !== null && $types !== '' ? [$types] : [];
            }

            ConfiguredPlayingTeamPlayer::where('team_id', $request->team_id)
                ->where('match_id', $request->match_id)
                ->where('game_type', $request->game_type)
                ->where('team_type', 2)
                ->delete();
            foreach ($playerIds as $index => $playerId) {

                    ConfiguredPlayingTeamPlayer::create([
                        'team_id' => $request->team_id,
                        'match_id' => $request->match_id,
                        'type' => $types[$index] ?? 'offensive',
                        'team_type' => 2,
                        'game_type' => $request->game_type,
                        'player_id' => $request->game_type == 1 ? $playerId : null,
                        'practice_player_id' => $request->game_type != 1 ? $playerId : null,
                    ],[]);
        
            }
           DB::commit();
           PersionalGrouping::pruneAllStaleRepairsAfterConfigureSave(
               (int) $request->team_id,
               (int) $request->match_id,
               (int) $request->game_type
           );
           return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure Player successFully");
        } catch (\Throwable $th) {
          DB::rollBack();
          return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
        }
    }
    public function view(Request $request)
    {
     

        // Base query (runs in all cases)
        $query = ConfiguredPlayingTeamPlayer::with(
            'player.teamPlayerPosition',
            'player.player.playerPosition',
            'practice_player.positions',
        )
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
            $playIds = $request->input('play_id', []);
            if (!is_array($playIds)) {
                $playIds = $playIds !== null && $playIds !== '' ? [$playIds] : [];
            }
            $playIds = array_values(array_unique(array_filter(array_map('intval', $playIds), fn ($id) => $id > 0)));

            ConfigurePlay::where([
                'user_id' => auth()->user()->id,
                'league_id' => $request->league_id,
                'match_id' => $request->matchId,
            ])->delete();
            foreach ($playIds as $value) {

                $configureFormation = new ConfigurePlay();
                $configureFormation->user_id = auth()->user()->id;
                $configureFormation->play_id = $value;
                $configureFormation->match_id = $request->matchId;
                $configureFormation->league_id = $request->league_id;
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
        $configure = ConfigurePlay::with('league', 'play')
            ->where('user_id', auth()->id())
            ->where('league_id', $request->league_id)
            ->where('match_id', $request->matchId)
            ->get();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure Play List", $configure);
  
    }
     public function configurePlayDefensiveView(Request $request)
    {
        $configure = ConfigureDefensivePlay::with('league', 'play')
            ->where('user_id', auth()->id())
            ->where('league_id', $request->league_id)
            ->where('game_id', $request->matchId)
            ->get();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure Play List", $configure);
  
    }
    
}
