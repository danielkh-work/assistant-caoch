<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlayerController extends Controller
{
    public  function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $player = new Player();
            $player->name = $request->name;
            $player->user_id = auth()->user()->id;
            $player->number=  $request->number;
            $player->position = $request->position;
            $player->size= $request->size;
            $player->speed= $request->speed;
            $player->strength =  $request->strength;
            if($request->hasFile('image'))
            {
                $path =  uploadImage($request->image,'player');
                $player->image =$path;
            }
            $player->save();
            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player Added SuccessFully ", $player);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
        }
    }

    public function list(Request $request)
    {
        $players = Player::where('user_id',auth()->user()->id)->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player List  ", $players);
    }
    public function update(Request $request,$id)
    {
        DB::beginTransaction();
        try {
            $player =  Player::find($id);
            $player->name = $request->name;
            $player->number=  $request->number;
            $player->position = $request->position;
            $player->size= $request->size;
            $player->speed= $request->speed;
            $player->strength =  $request->strength;
            if($request->hasFile('image'))
            {

                $path =  uploadImage($request->image,'player');
                $player->image =$path ;
            }
            $player->save();

            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player Updated SuccessFully ", $player);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
        }
    }

    public function delete($id)
    {
        $player =  Player::find($id);
        $player->delete();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player Deleted SuccessFully ");
    }
    public function view($id)
    {
        $player =  Player::find($id);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player Details", $player);
    }
}
