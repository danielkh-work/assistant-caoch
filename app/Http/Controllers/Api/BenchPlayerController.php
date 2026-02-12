<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BenchPlayer;
use App\Models\ConfiguredPlayingTeamPlayer;
use Illuminate\Http\Request;
use App\Http\Responses\BaseResponse;
use Illuminate\Support\Facades\DB;
use App\Models\OpponentTeamPackage;
use App\Models\PersionalGrouping;



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
                        'player' => $benchPlayer->player,
                        'name' => $benchPlayer->player->player->name,
                        'number' => $benchPlayer->player->number,
                        'size' => $benchPlayer->player->size,
                        'position_value' => $benchPlayer->player->position_value,
                        'squad' => 3,
                        'position' => $benchPlayer->player->position,
                        'speed' => $benchPlayer->player->speed,
                        'strength' => $benchPlayer->player->strength,
                        'ofp' => $benchPlayer->player->ofp,
                        'rpp' => $benchPlayer->rpp,
                        'weight' => $benchPlayer->player->weight,
                        'height' => $benchPlayer->player->height,
                        'dob' => $benchPlayer->player->player->dob,
                    ];
                })
                ->values(); // reindex if needed

        \Log::info(['offense and defense players....'=> $configure]);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "bench Player List",$configure);
     
    }
    
    public function getCount($gameId)
    {
        $benchPlayerCount = BenchPlayer::where('game_id', $gameId)->count();

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            "Bench player count fetched successfully",
            ['count' => $benchPlayerCount] // wrap count in an array/object
        );
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
                'player' => $benchPlayer->player,
                'name' => optional($benchPlayer->player)->player->name ?? null,
                'number' => optional($benchPlayer->player)->number ?? null,
                'size' => optional($benchPlayer->player)->size ?? null,
                'squad' => 3,
                'position_value' => optional($benchPlayer->player)->position_value ?? null,
                'position' => optional($benchPlayer->player)->position ?? null,
                'speed' => optional($benchPlayer->player)->speed ?? null,
                'strength' => optional($benchPlayer->player)->strength ?? null,
                'ofp' => optional($benchPlayer->player)->ofp ?? null,
                'rpp' => $benchPlayer->rpp,
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
        $player_type=$request->get('playerType');
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
                'player_type' =>  $player_type,
                'rpp' =>  $item['rpp']
            ];
        }
         BenchPlayer::insert($savedPlayers);
         return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Bench Player Add Successfully", $savedPlayers);
       
    }

 public function rppUpdate(Request $request, $leagueId)
{
            
            $data=$request->all();
     
            $player = BenchPlayer::where('league_id', $leagueId)
                ->where('player_id',  $data['id'])
                ->where('team_id',$data['player']['team_id'])
                ->firstOrFail();

           \Log::info(['update rpp'=> $player]);

            $player->update([
                'rpp' => $data['rpp']
            ]);

           return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "update rpp  Successfully", []);
    }
   public function shufflePlayers(Request $request)
    {
        
        
      
        $offensePlayers = $request->input('offensePlayers', []);
        $benchPlayers   = $request->input('benchPlayers', []);
        $teamId         = $request->input('teamId');
        $gameId         = $request->input('gameId');
        $playerType     = $request->input('playerType'); 
        $team_type     = $request->input('team_type'); 
        $leagueId     = $request->input('leagueId'); 
        $type     = $request->input('type'); 
        // $group_id     = $request->input('group_id'); 

        
        $offenseIds = array_map(fn($player) => $player['id'], $offensePlayers);
        $benchIds   = array_map(fn($player) => $player['id'], $benchPlayers);

 
        DB::transaction(function() use ($offenseIds, $benchIds, $teamId,$leagueId,$type, $gameId, $playerType,$team_type) {


        if (!empty($offenseIds)) {
            BenchPlayer::where('team_id', $teamId)
                ->where('game_id', $gameId)
                ->where('player_type', $playerType)
                ->whereIn('player_id', $offenseIds)
                ->delete();
        }

        
            if (!empty($benchIds)) {
                    $insertData = [];
                    foreach ($benchIds as $playerId) {
                        $insertData[] = [
                            'team_id'   => $teamId,
                            'game_id'   => $gameId,
                            'player_id' => $playerId,
                            'league_id' => $leagueId,
                            'player_type' => $playerType,
                            'type' => $type,
                           
                        ];
                    }

                    BenchPlayer::insert($insertData);
                    }
                
                    if (!empty($benchIds)) {
                        ConfiguredPlayingTeamPlayer::where('team_id', $teamId)
                            ->where('match_id', $gameId)
                            ->whereIn('player_id', $benchIds)
                           
                            ->delete();
                    }

                    if (!empty($offenseIds)) {
                        
                            $insertDataa = [];
                            foreach ($offenseIds as $playerId) {
                                $insertDataa[] = [
                                    'team_id'   => $teamId,
                                    'match_id'   => $gameId,
                                    'player_id' => $playerId,
                                    'team_type' => $team_type
                                   
                                ];
                            }

                            ConfiguredPlayingTeamPlayer::insert($insertDataa);
                        }


                         

                        
                        // $group_type = ($playerType === 'offence') ? 'offense' : 'defensive';

                       
                        // PersionalGrouping::where('game_id', $gameId)
                        //     ->where('team_id', $teamId)
                        //     ->where('type', $group_type)
                        //     ->where('id', '<>', $group_id) 
                        //     ->update(['status' => 'substituted']);

                       
                        // PersionalGrouping::where('team_id', $teamId)->where('type', $group_type)->update(['status' => null]);

                
                });
  
         return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, " Player Shuffle  Successfully", []);
       
    }

    public function store(Request $request)
    {

       
        $benchData=$request->get('benchPlayers');
        $team_id=$request->get('teamId');
        $player_type=$request->get('playerType');
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
                'player_type' =>  $player_type,
                'rpp' =>  $item['rpp'],
            ];
        }
        \Log::info(['bench Data inserted'=>$savedPlayers]);
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
