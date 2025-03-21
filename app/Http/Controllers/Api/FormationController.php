<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\Formation;
use App\Models\FormationData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormationController extends Controller
{
    public function store(Request $request)
    {
       
        DB::beginTransaction();
        try {
          
            $formation  =  new Formation();
            $formation->league_id = $request['league_id'];
            $formation->formation = $request['formation_name'];
            $formation->base_64 = $request['image'];
          
                $path =  storeBase64Image($request['image']);

                $formation->image =  $path ;
            

            $formation->save();
            foreach ($request->players as $key => $value) {
                $f_data =new FormationData;
                $f_data->formation_id =  $formation->id;
                $f_data->name = $value['name'];
                $f_data->y=$value['y'];
                $f_data->x=$value['x'];
                $f_data->type=$value['type'];
                $f_data->player_number=$value['player_number'];
                $f_data->save();
            }
            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Formation save successFully", $formation);
        } catch (\Throwable $th) {
          DB::rollBack();
          return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
        }
    }

    public function view(Request $request)
    {
        $formation =  Formation::with('formation_data')->find($request->id);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Formation view", $formation);
    }
}
