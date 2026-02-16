<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\LeagueTeam;
use App\Models\Team;
use App\Models\TeamPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeamController extends Controller
{
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $team =  new Team();
            $team->name =  $request->team_name;
            if ($request->hasFile('image')) {
                $path =  uploadImage($request->image, 'uploads');
                $team->image = $path;
            }
            $team->save();
           

            
            foreach ($request->playerid as  $key=> $id) {
                
                $t_player =  new TeamPlayer();
                $t_player->team_id = $team->id;
                $t_player->player_id = $id;
                $t_player->size = 0;
                $t_player->position = 0;
                $t_player->strength = 0;
                $t_player->type = $request->playertype[$key];
                $t_player->save();
            }
            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team save successFully", $team);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
        }
    }

    public function index()
    {
        $team =  Team::with('teamplayer.player')->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team list", $team);
    }
    public function view($id)
    {
        $team =  LeagueTeam::with(['teamplayer.player','practiceTeamplayer.TeamPlayer.player'])->find($id);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team view", $team);
    }
     public function practiceTeamList($id)
    {
        $team =  LeagueTeam::with('teamplayer.player')->where('type',1)->where('league_id',$id)->first();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team view", $team);
    }

            public function update(Request $request, $id)
        {
            \Log::info(['data team update records' => $request->all()]);
            \Log::info(['image' => $request->image]);

            DB::beginTransaction();
            try {
                // Update team info
                $team = LeagueTeam::find($id);
                $team->team_name = $request->team_name;

                if ($request->hasFile('image')) {
                    $path = uploadImage($request->image, 'uploads');
                    $team->image = $path;
                }

                $team->save();

                // Delete existing players
                TeamPlayer::where('team_id', $id)->delete();

                // Decode players JSON
                $players = json_decode($request->players, true) ?? [];

                foreach ($players as $player) {
                    $t_player = new TeamPlayer();
                    $t_player->team_id = $team->id;
                    $t_player->player_id = $player['player_id'] ?? null;
                    $t_player->league_id = $request->league_id ?? null;
                    $t_player->type = $player['playertype'] ?? $player['target'] ?? null;

                    // Sanitize helper
                    $sanitize = function ($value) {
                        return in_array($value, ['N/A', '', null, 'null'], true) ? null : $value;
                    };

                    $t_player->name = $sanitize($player['name'] ?? null);
                    $t_player->position_value = $sanitize($player['position'] ?? null);
                    $t_player->position = $sanitize($player['target'] ?? null);
                    $t_player->number = $sanitize($player['number'] ?? null);
                    $t_player->size = $sanitize($player['size'] ?? null);
                    $t_player->speed = $sanitize($player['speed'] ?? 0);
                    $t_player->strength = $sanitize($player['strength'] ?? null);
                    $t_player->weight = $sanitize($player['weight'] ?? null);
                    $t_player->height = $sanitize($player['height'] ?? null);
                    $t_player->rpp = $sanitize($player['ofp'] ?? null);

                    // DOB parsing
                    if (!empty($player['dob']) && $player['dob'] !== 'N/A') {
                        try {
                            $t_player->dob = \Carbon\Carbon::parse($player['dob'])->format('Y-m-d');
                        } catch (\Exception $e) {
                            $t_player->dob = null;
                        }
                    } else {
                        $t_player->dob = null;
                    }

                    $t_player->save();
                }

                DB::commit();

                return new BaseResponse(
                    STATUS_CODE_OK,
                    STATUS_CODE_OK,
                    "Team updated successfully",
                    $team
                );

            } catch (\Throwable $th) {
                DB::rollBack();

                return new BaseResponse(
                    STATUS_CODE_UNPROCESSABLE,
                    STATUS_CODE_UNPROCESSABLE,
                    $th->getMessage()
                );
            }
        }


    // public function update(Request $request ,$id)
    // {

           
   
    //     DB::beginTransaction();
    //     try {

    //         $team = LeagueTeam::find($id);
    //         $team->team_name =  $request->team_name;
    //         if ($request->hasFile('image')) {
    //             $path =  uploadImage($request->image, 'uploads');
    //             $team->image = $path;
    //         }
    //         $team->save();

    //         TeamPlayer::where('team_id',$id)->delete();
    //         foreach ($request->playerid as $key => $id) {
                
    //             $t_player = new TeamPlayer();
    //             $t_player->team_id = $team->id;
    //             $t_player->player_id = $id;
    //             $t_player->league_id = $request->league_id;
              
    //             $t_player->type = $request->playertype[$key] ?? null;
              
    //             $sanitize = function ($value) {
    //                 return (in_array($value, ['N/A','',"",null,NULL,'null'], true)) ? null : $value;
    //              };

            
    //             $t_player->name = $sanitize($request->name[$key]);
    //             $t_player->position_value = $sanitize($request->position_value[$key]);
    //             $t_player->position = $sanitize($request->position[$key]);
    //             $t_player->number = $sanitize($request->number[$key]);
    //             $t_player->size = $sanitize($request->size[$key]);
             
    //             $t_player->speed = $sanitize($request->speed[$key]) ?? 0;

    //             $t_player->strength = $sanitize($request->strength[$key]);
    //             $t_player->weight = $sanitize($request->weight[$key]);
    //             $t_player->height = $sanitize($request->height[$key]);


               
    //             // $t_player->name = $sanitize($request->name[$key] ?? null);
    //             // $t_player->position_value = $sanitize($request->position_value[$key] ?? null);
    //             // $t_player->position = $sanitize($request->position[$key] ?? null);
    //             // $t_player->number = $sanitize($request->number[$key] ?? null);
    //             // $t_player->size = $sanitize($request->size[$key] ?? null);
    //             // $t_player->speed = $sanitize($request->speed[$key] ?? null);
    //             // $t_player->strength = $sanitize($request->strength[$key] ?? null);
    //             // $t_player->weight = $sanitize($request->weight[$key] ?? null);
    //             // $t_player->height = $sanitize($request->height[$key] ?? null);


                

    //             if (!empty($request->dob[$key]) && $request->dob[$key] !== 'N/A') {
    //                 try {
    //                     $t_player->dob = Carbon::parse($request->dob[$key])->format('Y-m-d');
    //                 } catch (\Exception $e) {
    //                     $t_player->dob = null;
    //                 }
    //             } else {
    //                 $t_player->dob = null;
    //             }
               
    //             $t_player->rpp = $sanitize($request->ofp[$key]?? null);

    //           try {
    //                 $t_player->save();
    //             } catch (\Exception $e) {
    //                 dd([
    //                 'message' => $e->getMessage(),
    //                 'line' => $e->getLine(),
    //                 'file' => $e->getFile(),
    //                 ]);
    //             }

                       
     
    //         }
              


    //         // foreach ($request->playerid as $key=>$id) {
    //         //     $t_player =  new TeamPlayer();
    //         //     $t_player->team_id = $team->id;
    //         //     $t_player->player_id = $id;
               
    //         //     $t_player->speed = 0;
    //         //     $t_player->league_id = $request->league_id;
    //         //     $t_player->type = $request->playertype[$key];
    //         //     $t_player->name = $request->name[$key];
    //         //     $t_player->position_value = $request->position_value[$key];
    //         //     $t_player->position = $request->position[$key];
    //         //     $t_player->number = $request->number[$key];
    //         //     $t_player->size = $request->size[$key];
    //         //     $t_player->speed = $request->speed[$key];
    //         //     $t_player->strength = $request->strength[$key];
    //         //     $t_player->weight = $request->weight[$key];
    //         //     $t_player->height = $request->height[$key];
    //         //     if (!empty($request->dob[$key])) {
    //         //         try {
    //         //         $t_player->dob = Carbon::parse($request->dob[$key])->format('Y-m-d');
    //         //         } catch (\Exception $e) {
    //         //         $t_player->dob = null;
    //         //         }
    //         //     }
    //         //     $t_player->ofp = $request->ofp[$key];
    //         //     $t_player->save();
    //         // }
    //         DB::commit();
    //         return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team Update successFully", $team);
    //     } catch (\Throwable $th) {
    //         DB::rollBack();
    //         return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
    //     }
    // }

    public  function teamListByLeague(Request $request)
    {
        $team = LeagueTeam::where('league_id',$request->id)->get();
     
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team List", $team);
    }

    public  function teamListForPlayMode(Request $request)
    {
        $team = LeagueTeam::where('league_id',$request->id)->where('is_practice',0)->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team List", $team);
    }

    
}
