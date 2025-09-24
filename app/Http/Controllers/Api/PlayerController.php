<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\TeamPlayer;

class PlayerController extends Controller
{
    //  comment By noor
    // public  function store(Request $request)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $player = new Player();
    //         $player->name = $request->name;
    //         $player->user_id = auth()->user()->id;
    //         $player->number=  $request->number;
    //         $player->position = $request->position;
    //         $player->size= $request->size;
    //         $player->speed= $request->speed;
    //         $player->strength =  $request->strength;
    //         if($request->hasFile('image'))
    //         {
    //             $path =  uploadImage($request->image,'player');
    //             $player->image =$path;
    //         }
    //         $player->save();
    //         DB::commit();
    //         return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player Added SuccessFully ", $player);
    //     } catch (\Throwable $th) {
    //         DB::rollBack();
    //         return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
    //     }
    // }
    public  function store(Request $request)
    {

        \Log::info(['team'=>$request->all()]);
        DB::beginTransaction();
        try {
            $player = new Player();
           $type= $request->type;
           \Log::info(['type'=>$type]);
            if ($type === 'player') {
                 $player->user_id = auth()->id();
                \Log::info(['type nformaion'=>$type]);
            } elseif ($type === 'league') {
                 \Log::info(['type  league nformaion'=>$request->league_id.' '.$request->league_id]);
                $player->league_id = $request->league_id;
                
            } elseif ($type === 'team') {
                 \Log::info(['type  league nformaion'=>$type]);
                $player->user_id = auth()->id();
            }else{

            }
           
            $player->name = $request->name;
            $player->number=  $request->number;
            $player->position = $request->position;
            $player->size= $request->size;
            $player->speed= $request->speed;
            $player->weight= $request->weight;
            $player->height= $request->height;
            $player->dob= $request->dob;
            $player->ofp= $request->ofp;
            
            $player->strength =  $request->strength;
            $player->position_value =  $request->positionValue;
            if($request->hasFile('image'))
            {
                $path =  uploadImage($request->image,'player');
                $player->image =$path;
            }
            $player->save();
           if($type === 'team'){
                    DB::table('team_players')->insert([
                        'player_id' => $player->id,
                        'team_id' => $request->team_id,
                        'name' => $player->name,
                        'number' => $player->number,
                        'position' => $player->position,
                        'size' => $player->size,
                        'speed' => $player->speed,
                        'strength' => $player->strength,
                        'weight' => $player->weight,
                        'height' => $player->height,
                        'dob' => $player->dob,
                        'image' => $player->image,
                        'position_value' => $player->position_value,
                        'ofp' => $player->ofp,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player Added SuccessFully ", $player);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
        }
    }
    public function list(Request $request)
    {
        $userRoleIds = auth()->user()->roles->pluck('id');
        $players = Player::with(['roles' => function ($query) use ($userRoleIds) {
             $query->whereIn('roleables.role_id', $userRoleIds);
        }])->orderBy('name')->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player List  ", $players);
    }
    public function update(Request $request,$id)
    { 
        DB::beginTransaction();
        try {

          
           $type = $request->type;
           if ($type == 'team_player') {
            \Log::info(['type log of play when edit',$type]);
            $player = DB::table('team_players')->where('player_id', $id)->where('team_id', $request->team_id)->first();

            if (!$player) {
                return new BaseResponse(404, 404, "Team Player not found.");
            }

            // Build update data array
            $updateData = [
                'name' => $request->name,
                'number' => $request->number,
                'position' => $request->position,
                'size' => $request->size,
                'speed' => $request->speed,
                'strength' => $request->strength,
                'weight' => $request->weight,
                'height' => $request->height,
                'ofp' => $request->ofp,
                'position_value' => $request->positionValue,
                'updated_at' => now()
            ];

            try {
                $dob = Carbon::parse($request->dob);
                $updateData['dob'] = $dob;
            } catch (\Exception $e) {
                $updateData['dob'] = null;
            }

            if ($request->hasFile('image')) {
                $path = uploadImage($request->image, 'player');
                $updateData['image'] = $path;
            }

         DB::table('team_players')->where('player_id', $id)->where('team_id', $request->team_id)->update($updateData);
        $player = DB::table('team_players')
            ->where('player_id', $id)
            ->where('team_id', $request->team_id)
            ->first();

        } else {
          \Log::info(['type log of play when edit else part']);
            $player =  Player::find($id);
            $player->name = $request->name;
            $player->number=  $request->number;
            $player->position = $request->position;
            $player->size= $request->size;
            $player->speed= $request->speed;
            $player->strength =  $request->strength;
             $player->weight= $request->weight;
            $player->height= $request->height;
            try {
            $dob = Carbon::parse($request->dob);
            } catch (\Exception $e) {
            $dob = null;
            }
             $player->dob=  $dob;
             $player->ofp= $request->ofp;
             $player->position_value =  $request->positionValue;
            if($request->hasFile('image'))
            {

                $path =  uploadImage($request->image,'player');
                $player->image =$path ;
            }
            $player->save();


          }
            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player Updated SuccessFully ", $player);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
        }
    }
    
    public function updateOFP(Request $request, $id)
    {
        \Log::info(['data'=>$request->all()]);
        $request->validate([
            'ofp' => 'required|integer|min:0|max:100',
        ]);

        $teamPlayer = TeamPlayer::findOrFail($id);
        $teamPlayer->ofp = $request->input('ofp');
        $teamPlayer->save();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player OFP Updated SuccessFully ", $teamPlayer);
    }
    public function delete($id,$team_id)
    {
        $player =  Player::find($id);
        $player->teams()->detach($team_id);
        // $player->delete();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player is  Deleted from the team SuccessFully ");
    }
     public function deletePlayer($id,$team_id)
    {   
        $player =  Player::find($id);
        $player->teams()->detach($team_id);
        $player->delete();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player is  Deleted from the SuccessFully ");
    }
    public function view($id)
    {
        $player =  Player::find($id);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player Details", $player);
    }
}
