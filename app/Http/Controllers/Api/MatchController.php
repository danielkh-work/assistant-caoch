<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\League;

class MatchController extends Controller
{
    public function index(League $league) {
        $matches = $league->matches()->with(['myTeam', 'opponentTeam'])->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Matches List  ", $matches);
    }
}
