<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\Request;
use App\Http\Responses\BaseResponse;
use App\Models\Penality;

class GameController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'league_id' => 'required|integer',
            'my_team_id' => 'required|integer',
            'oponent_team_id' => 'required|integer',
            'date' => 'required',
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
          \Log::info(['data'=>'checkit working ornot']);
            $gamesQuery = Game::with([
                'myTeam',
                'opponentTeam',
                'configuredPlays',
                'configureMyTeams',
                'configureVisitingTeams'
            ])
            
            ->where('league_id', $leagueId);

           
            if (request()->has('type')) {
                $gameType = request()->type;
                $gamesQuery->where('type', $gameType);
            }

            $games = $gamesQuery->get();
                    return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "games list", $games);
    }

    public function Penalities(Request $request)
    {
          $validated = $request->validate([
            'league_id'             => 'required|exists:leagues,id',
            'game_id'              => 'required',
            'penalty_type_id'       => 'required',
            'category'              => 'nullable',
            'severity'              => 'nullable',
            'yardage_penalty'       => 'nullable',
            'automatic_first_down'  => 'nullable',
            'loss_down'             => 'nullable',
            'accept_reject'         => 'nullable',
            'replay_down'           => 'nullable',
            'new_down'              => 'nullable',
            'new_ball_sport'        => 'nullable',
            'play_time'             => 'nullable',
            'setuation'             => 'nullable',
            'referee'               => 'nullable',
            'notes_description'     => 'nullable',
        ]);

        // ✅ Store penalty
        $penalty = Penality::create([
            'league_id'             => $validated['league_id'],
            'game_id'              => $validated['game_id'],
            'penalty_type_id'       => $validated['penalty_type_id'],
            'category'              => $validated['category'] ?? null,
            'severity'              => $validated['severity'] ?? null,
            'yardage_penalty'       => $validated['yardage_penalty'] ?? null,
            'automatic_first_down'  => $validated['automatic_first_down'] ?? null,
            'loss_down'             => $validated['loss_down'] ?? null,
            'accept_reject'         => $validated['accept_reject'] ?? null,
            'replay_down'           => $validated['replay_down'] ?? null,
            'new_down'              => $validated['new_down'] ?? null,
            'new_ball_sport'        => $validated['new_ball_sport'] ?? null,
            'play_time'             => $validated['play_time'] ?? null,
            'setuation'             => $validated['setuation'] ?? null,
            'referee'               => $validated['referee'] ?? null,
            'notes_description'     => $validated['notes_description'] ?? null,
        ]);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "'Penalty created successfully", $penalty);
    }

    public function penaltyList(Request $request)
    {
       $penalties = Penality::where([
        'league_id' => $request->league_id,
        'game_id'   => $request->game_id,
        ])
        ->orderBy('id', 'desc') // or ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($penalty) {
            // Format created_at as American 12-hour time
            $penalty->time_only = $penalty->created_at->format('h:i A');
            return $penalty;
        });
       return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "'Penalty List", $penalties);
  
    }


     public function delete(Request $request)
    {
        $game = Game::find($request->id);
        if ($game)
            $game->delete();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Game Delete Successfully ");
    }


    
    
}
