<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Live Session Inactivity Timeout
    |--------------------------------------------------------------------------
    |
    | A match/practice session is considered abandoned when its live scoreboard
    | has not been touched by a broadcast or scoreboard poll for this many
    | minutes. Normal EndMatch requests still end sessions immediately.
    |
    */
    'live_session_timeout_minutes' => env('GAME_MODE_LIVE_TIMEOUT_MINUTES', 30),
];
