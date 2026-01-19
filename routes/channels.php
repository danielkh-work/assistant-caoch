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



