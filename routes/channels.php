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





