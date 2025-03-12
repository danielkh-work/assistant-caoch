<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\Leaque;
use App\Models\LeaqueRule;
use App\Models\Player;
use App\Models\Sport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SportController extends Controller
{
    public function sport(Request $request){
        $sport = Sport::all();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "sport", $sport);
    }

    public function leaque(Request $request)
    {
        $leaque = LeaqueRule::all();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "leaque Rule ", $leaque);
    }
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
           $leaque =  new Leaque;
           $leaque->sport_id=$request->sport_id;
           $leaque->leaque_rule_id=$request->leaque_rule_id;
           $leaque->title=$request->title;
           $leaque->number_of_downs=$request->number_of_downs;
           $leaque->length_of_field=$request->length_of_field;
           $leaque->number_of_timeouts=$request->number_of_timeouts;
           $leaque->clock_time=$request->clock_time;
           $leaque->number_of_quarters=$request->number_of_quarters;
           $leaque->length_of_quarters=$request->length_of_quarters;
           $leaque->stop_time_reason=$request->stop_time_reason;
           $leaque->overtime_rules=$request->overtime_rules;
           $leaque->number_of_players=$request->number_of_players;
           $leaque->flag_tbd =$request->flag_tbd;
           $leaque->save();
           DB::commit();
           return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "leaque Created SuccessFully ", $leaque);
        } catch (\Throwable $th) {
          DB::rollBack();
          return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
        }
    }

    public  function addPlayer(Request $request)
    {
        DB::beginTransaction();
        try {
            $player = new Player();
            $player->name = $request->name;
            $player->number=  $request->number;
            $player->position = $request->position;
            $player->size= $request->size;
            $player->speed= $request->speed;
            $player->strength =  $request->strength;
            $player->save();
            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player Added SuccessFully ", $player);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
        }
    }
}
