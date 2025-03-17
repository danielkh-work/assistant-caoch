<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\Formation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormationController extends Controller
{
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $formation  =  new Formation();
            $formation->leaque_id = $request->leaque_id;
            $formation->formation = $request->formation_name;
            if ($request->hasFile('image')) {

                $path =  uploadImage($request->image, 'public');
                $formation->image =  $path ;
            }

            $formation->save();
            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Formation save successFully", $formation);
        } catch (\Throwable $th) {
          DB::rollBack();
          return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
        }
    }
}
