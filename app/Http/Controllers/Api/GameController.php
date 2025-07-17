<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\Request;
use App\Http\Responses\BaseResponse;

class GameController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'league_id' => 'required|integer',
            'my_team_id' => 'required|integer',
            'oponent_team_id' => 'required|integer',
        ]);
        $validated['creator_id']= auth()->user()->id;
        $game = Game::create($validated);

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player Added SuccessFully ", $game);
    }

    public function index() {
        $games = Game::with(['myTeam', 'opponentTeam'])->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "games list", $games);
    }
    public function show($id)
    {
        $game = Game::with(['myTeam.teamplayer.player', 'opponentTeam.teamplayer.player'])->findOrFail($id);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "games", $game);
    }
    public function getOpponentMyTeamPlayers($id)
    {
        $game = Game::with(['configureMyTeams.player.player', 'configureVisitingTeams.player.player'])->findOrFail($id);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "games", $game);
    }
        public function getByLeague($leagueId)
    {
        $games = Game::with(['myTeam', 'opponentTeam','configuredPlays','configureMyTeams','configureVisitingTeams'])->where('league_id',$leagueId)->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "games list", $games);
    }
    
    
}
