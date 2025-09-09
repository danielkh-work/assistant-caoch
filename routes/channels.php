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




Broadcast::channel('user.{userId}', function ($user, $id) {
    if ($user->role === 'head_coach' && $user->id == $id) {
        return true;
    }
    if ($user->role === 'assistant_coach' && $user->head_coach_id == $id) {
        return true;
    }

    return false;
    //    \Log::info('Broadcast channel auth check', [
    //     'auth_user_id' => $user->id ?? null,
    //     'channel_id' => $id,
    // ]);
    // return (int) $user->id === (int) $id;

});

