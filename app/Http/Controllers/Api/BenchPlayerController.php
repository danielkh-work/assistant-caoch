<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BenchPlayer;
use App\Models\ConfiguredPlayingTeamPlayer;
use Illuminate\Http\Request;
use App\Http\Responses\BaseResponse;
use Illuminate\Support\Facades\DB;
use App\Models\OpponentTeamPackage;


class BenchPlayerController extends Controller
{
    public function index($teamId, $gameId)
    {
        
       
            $configure = BenchPlayer::with('player.player')
                ->where('game_id', $gameId)
                ->where('team_id', $teamId)
                ->where('type', 'myteam')
                ->get()
                // Filter out records where related player or nested player is missing
                ->filter(function ($benchPlayer) {
                    return $benchPlayer->player && $benchPlayer->player->player;
                })
                ->map(function ($benchPlayer) {
                    return [
                        'id' => $benchPlayer->player->id,
                        'name' => $benchPlayer->player->player->name,
                        'number' => $benchPlayer->player->number,
                        'size' => $benchPlayer->player->size,
                        'position_value' => $benchPlayer->player->position_value,
                        'squad' => 3,
                        'position' => $benchPlayer->player->position,
                        'speed' => $benchPlayer->player->speed,
                        'strength' => $benchPlayer->player->strength,
                        'ofp' => $benchPlayer->player->ofp,
                        'weight' => $benchPlayer->player->weight,
                        'height' => $benchPlayer->player->height,
                        'dob' => $benchPlayer->player->player->dob,
                    ];
                })
                ->values(); // reindex if needed

        
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "bench Player List",$configure);
     
    }

     public function getOpponentBenchPlayers($teamId, $gameId)
    {
        
      
       $configure = BenchPlayer::with('player.player')
        ->where('game_id', $gameId)
        ->where('team_id', $teamId)
        ->where('type', 'opponent')
        ->get()
        ->map(function ($benchPlayer) {
            return [
                'id' => optional($benchPlayer->player)->id ?? null,
                'name' => optional($benchPlayer->player)->player->name ?? null,
                'number' => optional($benchPlayer->player)->number ?? null,
                'size' => optional($benchPlayer->player)->size ?? null,
                'squad' => 3,
                'position_value' => optional($benchPlayer->player)->position_value ?? null,
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
     
         \Log::info(['opponent becnh'=>  $configure]);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "bench Player List",$configure);
     
    }


      public function opponentBenchPlayerStore(Request $request)
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
                'type' => 'opponent',
            ];
        }
         BenchPlayer::insert($savedPlayers);
         return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Bench Player Add Successfully", $savedPlayers);
       
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


        public function createMyTeamForPlayMode(Request $request)
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
               
                ->delete();
                $savedPlayers[] = [
                    'player_id' => $item['id'],
                    'match_id' =>   $game_id,
                    'team_id' => $team_id,
                    'team_type' => 1,
                    ];
            }
            ConfiguredPlayingTeamPlayer::insert($savedPlayers);
            foreach ($benchData as $index => $item) {
                BenchPlayer::where('game_id', $game_id)
                ->where('team_id', $team_id)
               
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

        public function createOpponentTeamForPlayMode(Request $request)
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
               
                ->delete();
                $savedPlayers[] = [
                    'player_id' => $item['id'],
                    'match_id' =>   $game_id,
                    'team_id' => $team_id,
                    'team_type' => 2,
                    ];
            }
            ConfiguredPlayingTeamPlayer::insert($savedPlayers);
            foreach ($benchData as $index => $item) {
                BenchPlayer::where('game_id', $game_id)
                ->where('team_id', $team_id)
               
                ->delete();
                $benchPlayers[] = [
                    'player_id' => $item['id'],
                    'game_id' =>   $game_id,
                    'team_id' => $team_id,
                    'league_id' =>  $league_id,
                    'type' => 'opponent',
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

    public function addOpponentPackage(Request $request)
    {
         $package = OpponentTeamPackage::createPackage($request->all());
        if (isset($package['grouping_count'])) {
            $package['count'] = $package['grouping_count'];
            unset($package['grouping_count']);
        }

         return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Bench Player Add Successfully",  $package );
    }


      public function getOpponentTeamPackages($gameId ,$teamId)
     {
           
           
            $packages = OpponentTeamPackage::getPackagesForOpponent($gameId,$teamId);
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "package lists",  $packages );
        }


    

}
