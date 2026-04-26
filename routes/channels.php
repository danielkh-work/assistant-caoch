<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/
Broadcast::routes(['middleware' => ['auth:sanctum']]);




Broadcast::channel('user.{userId}.game.{gameId}', function ($user, $userId, $gameId) {
    if ($user->role === 'head_coach' && $user->id == $userId) return true;
    if ($user->role === 'assistant_coach' && $user->head_coach_id == $userId) return true;
    return false;
});

Broadcast::channel('headcoach.{headCoachId}.qb', function ($user, $headCoachId) {

     return true;
   // return $user->role === 'head_coach' ;

});
Broadcast::channel('headcoach.{headCoachId}.play', function ($user, $headCoachId) {

     return true;
   // return $user->role === 'head_coach' ;

});

Broadcast::channel('headcoach.{userId}', function ($user, $userId) {
    return true;
});
Broadcast::channel('qb-user', function ($user) {
    return true; // or check if $user can listen
});
Broadcast::channel('mobile.{mobileUserId}', function ($user, $mobileUserId) {
    return (int)$user->id === (int)$mobileUserId;
});
Broadcast::channel('coach-group.{headCoachId}', function ($user, $headCoachId) {
    return true;

});


Broadcast::channel('league.{leagueId}', function ($user, $leagueId) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'role' => $user->role,
        'head_coach_id' => $user->head_coach_id,
    ];
});
Broadcast::channel('league.global', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});

// use App\Http\Controllers\LeagueController;

// Route::get('/league/{leagueId}/state', [LeagueController::class, 'state']);

// public function state($leagueId)
// {
//     $league = League::findOrFail($leagueId);

//     return response()->json([
//         'real' => (bool) $league->is_real_match_started,
//         'practice' => (bool) $league->is_practice_match_started,
//     ]);
// }


// public function liveStatus($leagueId)
// {
//     $matches = \DB::table('matches')
//         ->where('league_id', $leagueId)
//         ->get();

//     $practice = $matches->firstWhere('game_mode', 'practice');
//     $real = $matches->firstWhere('game_mode', 'real');

//     return response()->json([
//         'practice_started' => $practice?->status === 'started',
//         'real_started' => $real?->status === 'started',
//         'current_active_match' => $matches->firstWhere('status', 'started')?->game_mode ?? null
//     ]);
// }


// const loadMatch = async () => {

//     const response = await fetch(`/api/league/${leagueId.value}/current-match`);
//     const data = await response.json();

//     if (data.match) {

//         gameId.value = data.match.id;

//         isModePlay.value = data.match.game_mode === 'play';
//         isPracticeMode.value = data.match.game_mode === 'practice';

//         // restore state instead of starting new game
//         if (data.match.status === 1) {
//             console.log("Match already started — resuming");
//         }

//     } else {
//         console.log("No active match — user can start new one");
//     }
// };

// Route::get('/league/{leagueId}/current-match', [LeagueController::class, 'currentMatch']);

// public function currentMatch($leagueId)
// {
//     $match = PlayGameMode::where('league_id', $leagueId)
//         ->whereIn('status', [0, 1]) // active or pending
//         ->latest()
//         ->first();

//     return response()->json([
//         'match' => $match
//     ]);
// }


// public function activeScoreboard($gameId)
// {
//        $game = PlayGameMode::where('league_id', $leagueId)
    //     ->where('status', 2) // 1 = started
    //     ->latest()
    //     ->first();

    // if (!$game) {
    //     return response()->json([
    //         'message' => 'No active match'
    //     ], 404);
    // }


//     if ($game->game_mode === 'practice') {

//         $score = PracticeWebsocketScoreboard::where('game_id', $gameId)->first();

//     } else {

//         $score = WebsocketScoreboard::where('game_id', $gameId)->first();
//     }

//     return response()->json([
//         'game_mode' => $game->game_mode,
//         'scoreboard' => $score
//     ]);
// }






