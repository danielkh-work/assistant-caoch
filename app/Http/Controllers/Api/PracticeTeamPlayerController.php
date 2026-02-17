<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeagueTeam;
use App\Models\PracticeTeamPlayer;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Responses\BaseResponse;

class PracticeTeamPlayerController extends Controller
{

        public function update(Request $request, $id)
        {
            DB::beginTransaction();
            try {

                $team = LeagueTeam::findOrFail($id);
                $team->team_name = $request->team_name;

                if ($request->hasFile('image')) {
                $path = uploadImage($request->image, 'uploads');
                $team->image = $path;
                }   

            $team->save();

            $players = json_decode($request->players, true) ?? [];

            $incomingPlayerIds = [];

            foreach ($players as $player) {

            $sanitize = function ($value) {
                return in_array($value, ['N/A', '', null, 'null'], true) ? null : $value;
            };

            $data = [
                'league_id' => $request->league_id ?? null,
                'type' => $player['playertype'] ?? $player['target'] ?? null,
                'name' => $sanitize($player['name'] ?? null),
                'position_value' => $sanitize($player['position'] ?? null),
                'position' => $sanitize($player['target'] ?? null),
                'number' => $sanitize($player['number'] ?? null),
                'size' => $sanitize($player['size'] ?? null),
                'speed' => $sanitize($player['speed'] ?? 0),
                'strength' => $sanitize($player['strength'] ?? null),
                'weight' => $sanitize($player['weight'] ?? null),
                'height' => $sanitize($player['height'] ?? null),
                'rpp' => $sanitize($player['ofp'] ?? null),
            ];

            // DOB
            if (!empty($player['dob']) && $player['dob'] !== 'N/A') {
                try {
                    $data['dob'] = \Carbon\Carbon::parse($player['dob'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $data['dob'] = null;
                }
            } else {
                $data['dob'] = null;
            }

            PracticeTeamPlayer::updateOrCreate(
                [
                    'team_id' => $team->id,
                    'player_id' => $player['player_id'] ?? null,
                ],
                $data
            );

            $incomingPlayerIds[] = $player['player_id'];
            }

            // Optional: delete players removed from frontend
            PracticeTeamPlayer::where('team_id', $team->id)
            ->whereNotIn('player_id', $incomingPlayerIds)
            ->delete();

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

        // public function update(Request $request, $id)
        // {
        //     DB::beginTransaction();
        //     try {

        //         // Update team info
        //         $team = LeagueTeam::find($id);
        //         $team->team_name = $request->team_name;

        //         if ($request->hasFile('image')) {
        //             $path = uploadImage($request->image, 'uploads');
        //             $team->image = $path;
        //         }

        //         $team->save();

        //         // Delete existing practice team players
        //         PracticeTeamPlayer::where('team_id', $id)->delete();

        //         // Decode players JSON from frontend
        //         $players = json_decode($request->players, true) ?? [];
        //         foreach ($players as $player) {
        //             $t_player = new PracticeTeamPlayer();
        //             $t_player->team_id = $team->id;
        //             $t_player->player_id = $player['player_id'] ?? null;
        //             $t_player->league_id = $request->league_id ?? null;
        //             $t_player->type = $player['playertype'] ?? $player['target'] ?? null;

        //             // Sanitization helper
        //             $sanitize = function ($value) {
        //                 return in_array($value, ['N/A', '', null, 'null'], true) ? null : $value;
        //             };

        //             $t_player->name = $sanitize($player['name'] ?? null);
        //             $t_player->position_value = $sanitize($player['position'] ?? null);
        //             $t_player->position = $sanitize($player['target'] ?? null);
        //             $t_player->number = $sanitize($player['number'] ?? null);
        //             $t_player->size = $sanitize($player['size'] ?? null);
        //             $t_player->speed = $sanitize($player['speed'] ?? 0);
        //             $t_player->strength = $sanitize($player['strength'] ?? null);
        //             $t_player->weight = $sanitize($player['weight'] ?? null);
        //             $t_player->height = $sanitize($player['height'] ?? null);
        //             $t_player->rpp = $sanitize($player['ofp'] ?? null);

        //             // DOB parsing
        //             if (!empty($player['dob']) && $player['dob'] !== 'N/A') {
        //                 try {
        //                     $t_player->dob = \Carbon\Carbon::parse($player['dob'])->format('Y-m-d');
        //                 } catch (\Exception $e) {
        //                     $t_player->dob = null;
        //                 }
        //             } else {
        //                 $t_player->dob = null;
        //             }

        //             $t_player->save();
        //         }

        //         DB::commit();

        //         return new BaseResponse(
        //             STATUS_CODE_OK,
        //             STATUS_CODE_OK,
        //             "Team updated successfully",
        //             $team
        //         );

        //     } catch (\Throwable $th) {
        //         DB::rollBack();

        //         return new BaseResponse(
        //             STATUS_CODE_UNPROCESSABLE,
        //             STATUS_CODE_UNPROCESSABLE,
        //             $th->getMessage()
        //         );
        //     }
        // }

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

    //         PracticeTeamPlayer::where('team_id',$id)->delete();
    //         foreach ($request->playerid as $key => $id) {
    //             $t_player = new PracticeTeamPlayer();
    //             $t_player->team_id = $team->id;
    //             $t_player->player_id = $id;
    //             $t_player->league_id = $request->league_id;
    //             $t_player->type = $request->playertype[$key];

    //             // Sanitize helper
    //             // $sanitize = function ($value) {
    //             //     return ($value === 'N/A' || $value === null || $value === '') ? null : $value;
    //             // };
    //             $sanitize = function ($value) {
    //                 return (in_array($value, ['N/A', '', null, 'null'], true)) ? null : $value;
    //             };



    //             $t_player->name = $sanitize($request->name[$key]);
    //             $t_player->position_value = $sanitize($request->position_value[$key]);
    //             $t_player->position = $sanitize($request->position[$key]);
    //             $t_player->number = $sanitize($request->number[$key]);
    //             $t_player->size = $sanitize($request->size[$key]);
    //             $t_player->speed = $sanitize($request->speed[$key]);
    //             $t_player->strength = $sanitize($request->strength[$key]);
    //             $t_player->weight = $sanitize($request->weight[$key]);
    //             $t_player->height = $sanitize($request->height[$key]);

    //             if (!empty($request->dob[$key]) && $request->dob[$key] !== 'N/A') {
    //                 try {
    //                     $t_player->dob = Carbon::parse($request->dob[$key])->format('Y-m-d');
    //                 } catch (\Exception $e) {
    //                     $t_player->dob = null;
    //                 }
    //             } else {
    //                 $t_player->dob = null;
    //             }

    //             $t_player->rpp = $sanitize($request->ofp[$key]);
    //             $t_player->save();
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
}
