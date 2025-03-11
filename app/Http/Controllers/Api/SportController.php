<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\Leaque;
use App\Models\Sport;
use Illuminate\Http\Request;

class SportController extends Controller
{
    public function sport(Request $request){
        $sport = Sport::all();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "sport", $sport);
    }

    public function leaque(Request $request)
    {
        $leaque = Leaque::all();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "leaque", $leaque);
    }
}
