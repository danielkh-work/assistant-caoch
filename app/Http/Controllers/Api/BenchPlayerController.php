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

    // public function index(Request $request,$teamId, $gameId)
    // {
    //        $isPractice = (bool) $request->get('isPractice', false);

       
    //         $configure = BenchPlayer::with('player.player')
    //             ->where('game_id', $gameId)
    //             ->where('team_id', $teamId)
    //             ->where('type', 'myteam')
    //             ->get()

    //             ->filter(function ($benchPlayer) {
    //                 return $benchPlayer->player && $benchPlayer->player->player;
    //             })
    //             ->map(function ($benchPlayer) {
    //                 return [
    //                     'bench_id'=>$benchPlayer->id,
    //                     'id' => $benchPlayer->player->id,
    //                     'player' => $benchPlayer->player,
    //                     'name' => $benchPlayer->player->player->name,
    //                     'number' => $benchPlayer->player->number,
    //                     'size' => $benchPlayer->player->size,
    //                     'position_value' => $benchPlayer->player->position_value,
    //                     'squad' => 3,
    //                     'position' => $benchPlayer->player->position,
    //                     'speed' => $benchPlayer->player->speed,
    //                     'strength' => $benchPlayer->player->strength,
    //                     'ofp' => $benchPlayer->player->ofp,
    //                     'rpp' => ($benchPlayer->rpp == 0) 
    //                         ? ($benchPlayer->player?->rpp ?? 0) 
    //                      : $benchPlayer->rpp,

    //                     'weight' => $benchPlayer->player->weight,
    //                     'height' => $benchPlayer->player->height,
    //                     'dob' => $benchPlayer->player->player->dob,
    //                 ];
    //             })
    //             ->values(); 

       
    //     return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "bench Player List",$configure);
     
    // }

//     public function index($teamId, $gameId, Request $request)
// {
//     $isPractice = (bool) $request->get('isPractice');

   
//     $benchPlayers = BenchPlayer::get()
//         ->where('game_id', $gameId)
//         ->where('team_id', $teamId)
//         ->where('type', 'myteam');

//     $configure = $benchPlayers
//         ->filter(function ($benchPlayer) use ($isPractice) {
//             if ($isPractice) {
               
//                 return !empty($benchPlayer->practice_player_id);
//             } else {
                
//                 return !empty($benchPlayer->player_id);
//             }
//         })
//         ->map(function ($benchPlayer) use ($isPractice) {

           
//             $id = $isPractice ? $benchPlayer->practice_player_id : $benchPlayer->player_id;
//             $name = $isPractice 
//                 ? $benchPlayer->practice_player->name ?? 'Unknown' 
//                 : $benchPlayer->player->player->name ?? 'Unknown';

//             $number = $isPractice
//                 ? $benchPlayer->practice_player->number ?? null
//                 : $benchPlayer->player->number ?? null;

//             $rpp = $benchPlayer->rpp ?: ($isPractice ? ($benchPlayer->practice_player->rpp ?? 0) : ($benchPlayer->player->rpp ?? 0));

//             return [
//                 'bench_id' => $benchPlayer->id,
//                 'id' => $id,
//                 'name' => $name,
//                 'number' => $number,
//                 'size' => $isPractice ? ($benchPlayer->practice_player->size ?? null) : $benchPlayer->player->size,
//                 'position_value' => $isPractice ? ($benchPlayer->practice_player->position_value ?? null) : $benchPlayer->player->position_value,
//                 'squad' => 3,
//                 'position' => $isPractice ? ($benchPlayer->practice_player->position ?? null) : $benchPlayer->player->position,
//                 'speed' => $isPractice ? ($benchPlayer->practice_player->speed ?? null) : $benchPlayer->player->speed,
//                 'strength' => $isPractice ? ($benchPlayer->practice_player->strength ?? null) : $benchPlayer->player->strength,
//                 'ofp' => $isPractice ? ($benchPlayer->practice_player->ofp ?? null) : $benchPlayer->player->ofp,
//                 'rpp' => $rpp,
//                 'weight' => $isPractice ? ($benchPlayer->practice_player->weight ?? null) : $benchPlayer->player->weight,
//                 'height' => $isPractice ? ($benchPlayer->practice_player->height ?? null) : $benchPlayer->player->height,
//                 'dob' => $isPractice ? ($benchPlayer->practice_player->dob ?? null) : $benchPlayer->player->player->dob,
//             ];
//         })
//         ->values(); 

//     return new BaseResponse(
//         STATUS_CODE_OK,
//         STATUS_CODE_OK,
//         "Bench Player List",
//         $configure
//     );
// }


public function index(Request $request, $teamId, $gameId)
{
    $isPractice = filter_var($request->get('isPractice', false), FILTER_VALIDATE_BOOLEAN);
    \Log::info(['isPractice' => $isPractice]);


    // Fetch all bench players for this team and game
    $benchPlayers = BenchPlayer::with('player.player','practice_player')->where('game_id', $gameId)
        ->where('team_id', $teamId)
        ->where('type', 'myteam')
        ->get();

        \Log::info(['data becnh players data to feth records'=>$benchPlayers]);

    $configure = $benchPlayers
        ->filter(function ($benchPlayer) use ($isPractice) {
            // If practice mode, check practice_player exists
            if ($isPractice) {
                return !empty($benchPlayer->practice_player_id);
            }
            // Normal mode, check player exists
              return $benchPlayer->player && $benchPlayer->player->player;
        })
        ->map(function ($benchPlayer) use ($isPractice) {

          if ($isPractice) {
                        $practice = $benchPlayer->practice_player;

                        $id = $benchPlayer->practice_player_id;
                        $player = null;
                        $name = $practice?->name ?? 'Unknown';
                        $number = $practice?->number ?? null;
                        $size = $practice?->size ?? null;
                        $position_value = $practice?->position_value ?? null;
                        $position = $practice?->position ?? null;
                        $speed = $practice?->speed ?? null;
                        $strength = $practice?->strength ?? null;
                        $ofp = $practice?->ofp ?? null;
                        $weight = $practice?->weight ?? null;
                        $height = $practice?->height ?? null;
                        $dob = $practice?->dob ?? null;
                        $rpp = $benchPlayer->rpp ?: ($practice?->rpp ?? 0);
}
 else {
                // Normal players
                $id = $benchPlayer->player->id;
                $player = $benchPlayer->player;
                $name = $benchPlayer->player->player->name;
                $number = $benchPlayer->player->number;
                $size = $benchPlayer->player->size;
                $position_value = $benchPlayer->player->position_value;
                $position = $benchPlayer->player->position;
                $speed = $benchPlayer->player->speed;
                $strength = $benchPlayer->player->strength;
                $ofp = $benchPlayer->player->ofp;
                $weight = $benchPlayer->player->weight;
                $height = $benchPlayer->player->height;
                $dob = $benchPlayer->player->player->dob;
                $rpp = ($benchPlayer->rpp == 0) ? ($benchPlayer->player?->rpp ?? 0) : $benchPlayer->rpp;
            }
  

            return [
                'bench_id' => $benchPlayer->id,
                'id' => $id,
                'player' => $player,
                'name' => $name,
                'number' => $number,
                'size' => $size,
                'position_value' => $position_value,
                'squad' => 3,
                'position' => $position,
                'speed' => $speed,
                'strength' => $strength,
                'ofp' => $ofp,
                'rpp' => $rpp,
                'weight' => $weight,
                'height' => $height,
                'dob' => $dob,
            ];
        })
        ->values(); // reindex

        \Log::info(['configure players index',$configure]);

    return new BaseResponse(
        STATUS_CODE_OK,
        STATUS_CODE_OK,
        "Bench Player List",
        $configure
    );
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

     public function getOpponentBenchPlayers(Request $request, $teamId, $gameId)
{
    // Get isPractice flag from query string
    $isPractice = filter_var($request->get('isPractice', false), FILTER_VALIDATE_BOOLEAN);
    \Log::info(['isPractice' => $isPractice]);

    // Fetch all opponent bench players
    $benchPlayers = BenchPlayer::with('player.player', 'practice_player')
        ->where('game_id', $gameId)
        ->where('team_id', $teamId)
        ->where('type', 'opponent')
        ->get();

    $configure = $benchPlayers
        ->filter(function ($benchPlayer) use ($isPractice) {
            // If practice mode, check practice_player exists
            if ($isPractice) {
                return !empty($benchPlayer->practice_player_id);
            }
            // Normal mode, check player exists
            return $benchPlayer->player && $benchPlayer->player->player;
        })
        ->map(function ($benchPlayer) use ($isPractice) {
            if ($isPractice) {
                $practice = $benchPlayer->practice_player;

                $id = $benchPlayer->practice_player_id;
                $player = null;
                $name = $practice?->name ?? 'Unknown';
                $number = $practice?->number ?? null;
                $size = $practice?->size ?? null;
                $position_value = $practice?->position_value ?? null;
                $position = $practice?->position ?? null;
                $speed = $practice?->speed ?? null;
                $strength = $practice?->strength ?? null;
                $ofp = $practice?->ofp ?? null;
                $weight = $practice?->weight ?? null;
                $height = $practice?->height ?? null;
                $dob = $practice?->dob ?? null;
                $rpp = $benchPlayer->rpp ?: ($practice?->rpp ?? 0);
            } else {
                $id = $benchPlayer->player->id ?? null;
                $player = $benchPlayer->player;
                $name = $benchPlayer->player->player->name ?? null;
                $number = $benchPlayer->player->number ?? null;
                $size = $benchPlayer->player->size ?? null;
                $position_value = $benchPlayer->player->position_value ?? null;
                $position = $benchPlayer->player->position ?? null;
                $speed = $benchPlayer->player->speed ?? null;
                $strength = $benchPlayer->player->strength ?? null;
                $ofp = $benchPlayer->player->ofp ?? null;
                $weight = $benchPlayer->player->weight ?? null;
                $height = $benchPlayer->player->height ?? null;
                $dob = $benchPlayer->player->player->dob ?? null;
                $rpp = ($benchPlayer->rpp == 0) ? ($benchPlayer->player?->rpp ?? 0) : $benchPlayer->rpp;
            }

            return [
                'bench_id' => $benchPlayer->id,
                'id' => $id,
                'player' => $player,
                'name' => $name,
                'number' => $number,
                'size' => $size,
                'position_value' => $position_value,
                'squad' => 3,
                'position' => $position,
                'speed' => $speed,
                'strength' => $strength,
                'ofp' => $ofp,
                'rpp' => $rpp,
                'weight' => $weight,
                'height' => $height,
                'dob' => $dob,
            ];
        })
        ->values(); // reindex

    \Log::info(['opponent bench data' => $configure]);

    return new BaseResponse(
        STATUS_CODE_OK,
        STATUS_CODE_OK,
        "Opponent Bench Player List",
        $configure
    );
}


    //  public function getOpponentBenchPlayers($teamId, $gameId)
    // {
        
      
    //    $configure = BenchPlayer::with('player.player','practice_player')
    //     ->where('game_id', $gameId)
    //     ->where('team_id', $teamId)
    //     ->where('type', 'opponent')
    //     ->get()
    //     ->map(function ($benchPlayer) {
    //         return [
    //             'bench_id'=>$benchPlayer->id,
    //             'id' => optional($benchPlayer->player)->id ?? null,
    //             'player' => $benchPlayer->player,
    //             'name' => optional($benchPlayer->player)->player->name ?? null,
    //             'number' => optional($benchPlayer->player)->number ?? null,
    //             'size' => optional($benchPlayer->player)->size ?? null,
    //             'squad' => 3,
    //             'position_value' => optional($benchPlayer->player)->position_value ?? null,
    //             'position' => optional($benchPlayer->player)->position ?? null,
    //             'speed' => optional($benchPlayer->player)->speed ?? null,
    //             'strength' => optional($benchPlayer->player)->strength ?? null,
    //             'ofp' => optional($benchPlayer->player)->ofp ?? null,
    //             'rpp' => ($benchPlayer->rpp == 0) 
    //                         ? ($benchPlayer->player?->rpp ?? 0) 
    //                      : $benchPlayer->rpp,
    //             'weight' => optional($benchPlayer->player)->weight ?? null,
    //             'height' => optional($benchPlayer->player)->height ?? null,
    //             'dob' => optional($benchPlayer->player)->player->dob ?? null,
                
    //             // Add other fields as needed
    //         ];
    //     });
     
    //      \Log::info(['opponent becnh'=>  $configure]);
    //     return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "bench Player List",$configure);
     
    // }


      public function opponentBenchPlayerStore(Request $request)
    {

       
        $benchData=$request->get('benchPlayers');
        $team_id=$request->get('teamId');
        $league_id=(int) $request->get('leagueId');
        $game_id=(int) $request->get('gameId');
        $player_type=$request->get('playerType');
        $isPractice = (bool) $request->get('isPractice');
        $savedPlayers=[];
        foreach ($benchData as $index => $item) {

         if ($isPractice) {
                ConfiguredPlayingTeamPlayer::where('match_id', $game_id)
                    ->where('team_id', $team_id)
                    ->where('practice_player_id', $item['id'])
                    ->delete();
            } else {
                ConfiguredPlayingTeamPlayer::where('match_id', $game_id)
                    ->where('team_id', $team_id)
                    ->where('player_id', $item['id'])
                    ->delete();
            }


        // ConfiguredPlayingTeamPlayer::where('match_id', $game_id)
        // ->where('team_id', $team_id)
        // ->where('player_id', $item['id'])
        // ->delete();

         $savedPlayers[] = [
                'player_id' => $isPractice ? null : $item['id'],
                'practice_player_id' => $isPractice ? $item['id'] : null,
                // 'player_id' => $item['id'],
                'game_id' =>   $game_id,
                'team_id' => $team_id,
                'league_id' =>  $league_id,
                'type' => 'opponent',
                'player_type' =>  $player_type,
                'rpp' =>  $item['rpp'],
            ];
            // $savedPlayers[] = [
            //     'player_id' => $item['id'],
            //     'game_id' =>   $game_id,
            //     'team_id' => $team_id,
            //     'league_id' =>  $league_id,
            //     'type' => 'opponent',
            //     'player_type' =>  $player_type,
            //     'rpp' =>  $item['rpp']
            // ];
        }
         BenchPlayer::insert($savedPlayers);
         return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Bench Player Add Successfully", $savedPlayers);
       
    }

 public function rppUpdate(Request $request, $id)
{
           $data=$request->all();
           $player = BenchPlayer::findOrFail($id);
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
        $isPractice = (bool) $request->get('isPractice');

        
      
        $savedPlayers=[];
        foreach ($benchData as $index => $item) {
        if ($isPractice) {
                ConfiguredPlayingTeamPlayer::where('match_id', $game_id)
                    ->where('team_id', $team_id)
                    ->where('practice_player_id', $item['id'])
                    ->delete();
            } else {
                ConfiguredPlayingTeamPlayer::where('match_id', $game_id)
                    ->where('team_id', $team_id)
                    ->where('player_id', $item['id'])
                    ->delete();
            }

            

            $savedPlayers[] = [
                'player_id' => $isPractice ? null : $item['id'],
                'practice_player_id' => $isPractice ? $item['id'] : null,
                // 'player_id' => $item['id'],
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
