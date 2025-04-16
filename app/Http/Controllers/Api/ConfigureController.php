<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\ConfiguredPlayingTeamPlayer;
use Illuminate\Support\Facades\DB;

class ConfigureController extends Controller
{
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            foreach($request->player_id as $key=>$id)
            {
                $configure = new ConfiguredPlayingTeamPlayer;
                $configure->team_id =  $request->team_id;
                $configure->player_id =  $id;
                $configure->type =  $request->type[$key];
                $configure->save();
            }
           DB::commit();
           return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "configure Player successFully");
        } catch (\Throwable $th) {
          DB::rollBack();
          return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
        }
    }
}
