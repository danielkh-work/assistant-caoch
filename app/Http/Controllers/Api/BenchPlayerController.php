<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BenchPlayer;
use App\Models\ConfiguredPlayingTeamPlayer;
use Illuminate\Http\Request;
use App\Http\Responses\BaseResponse;
use Illuminate\Support\Facades\DB;

class BenchPlayerController extends Controller
{
    public function index($teamId, $gameId)
    {
        
       
       $configure = BenchPlayer::with('player.player')
        ->where('game_id', $gameId)
        ->where('team_id', $teamId)
        ->get()
        ->map(function ($benchPlayer) {
            return [
                'id' => optional($benchPlayer->player)->id ?? null,
                'name' => optional($benchPlayer->player)->player->name ?? null,
                'number' => optional($benchPlayer->player)->number ?? null,
                'size' => optional($benchPlayer->player)->size ?? null,
                'squad' => 3,
                'position' => optional($benchPlayer->player)->position ?? null,
                'speed' => optional($benchPlayer->player)->speed ?? null,
                'strength' => optional($benchPlayer->player)->strength ?? null,
                'ofp' => optional($benchPlayer->player)->ofp ?? null,
                'weight' => optional($benchPlayer->player)->weight ?? null,
                'height' => optional($benchPlayer->player)->height ?? null,
                'dob' => optional($benchPlayer->player)->player->dob ?? null,
                
                // Add other fields as needed
            ];
        });
     
          \Log::info(['data'=>$configure ]);
    
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "bench Player List",$configure);
     
    }

    public function store(Request $request)
    {

       
        $benchData=$request->get('benchPlayers');
        $team_id=$request->get('teamId');
        $league_id=(int) $request->get('leagueId');
        $game_id=(int) $request->get('gameId');
      
        $savedPlayers=[];
        foreach ($benchData as $index => $item) {
        ConfiguredPlayingTeamPlayer::where('match_id', $game_id)
        ->where('team_id', $team_id)
        ->where('player_id', $item['id'])
        ->delete();
            $savedPlayers[] = [
                'player_id' => $item['id'],
                'game_id' =>   $game_id,
                'team_id' => $team_id,
                'league_id' =>  $league_id,
                'type' => 'myteam',
            ];
        }
         BenchPlayer::insert($savedPlayers);
         return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Bench Player Add Successfully", $savedPlayers);
       
    }


        public function createMyTeam(Request $request)
        {
           
        DB::beginTransaction();

        try {
                                     
            $configurePlayers =$request->get('configurePlayers');
            $benchData=$request->get('benchPlayers');
            $team_id=$request->get('teamId');
            $league_id=(int) $request->get('leagueId');
            $game_id=(int) $request->get('gameId');
            $savedPlayers=[];
            $benchPlayers=[];
            foreach ($configurePlayers as $index => $item) {
                ConfiguredPlayingTeamPlayer::where('match_id', $game_id)
                ->where('team_id', $team_id)
                ->where('player_id', $item['id'])
                ->delete();
                $savedPlayers[] = [
                    'player_id' => $item['id'],
                    'game_id' =>   $game_id,
                    'team_id' => $team_id,
                    'league_id' =>  $league_id,
                    'type' => 'myteam',
                    ];
            }
            ConfiguredPlayingTeamPlayer::insert($savedPlayers);
            foreach ($benchData as $index => $item) {
                BenchPlayer::where('match_id', $game_id)
                ->where('team_id', $team_id)
                ->where('player_id', $item['id'])
                ->delete();
                $benchPlayers[] = [
                    'player_id' => $item['id'],
                    'game_id' =>   $game_id,
                    'team_id' => $team_id,
                    'league_id' =>  $league_id,
                    'type' => 'myteam',
                    ];
               }
               BenchPlayer::insert($benchPlayers);
               DB::commit();
               return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Bench Player Add Successfully", [
                        'configured' => $savedPlayers,
                        'bench' => $benchPlayers,
                ]);
              } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Substitution failed.',
                        'error' => $e->getMessage(),
                    ], 500);
              }
        }

    public function destroy($id)
    {
        $benchPlayer = BenchPlayer::findOrFail($id);
        $benchPlayer->delete();

        return response()->json(['message' => 'Bench player removed.']);
    }
}
