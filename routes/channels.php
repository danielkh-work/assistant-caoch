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
Broadcast::channel('user.{userId}.game.{gameId}', function ($user, $id,$gameId) {
       \Log::info('Broadcast channel auth check', [
        'auth_user_id' => $user->id ?? null,
        'channel_id' => $id,
    ]);
    return (int) $user->id === (int) $id;

});

