<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fires when the scoreboard coach submits the result form for a play
 * (win/loss + yardage) — same channels as PlaySuggested, so the mobile app
 * can close its "waiting for coach" popup as soon as this lands.
 */
class PlayResultSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $playResult;

    public $coachGroupId;

    public int $leagueId;

    public function __construct($playResult, $coachGroupId, int $leagueId)
    {
        $this->playResult = $playResult;
        $this->coachGroupId = $coachGroupId;
        $this->leagueId = $leagueId;
    }

    public function broadcastOn()
    {
        $channels = [
            new PrivateChannel("headcoach.{$this->coachGroupId}.league.{$this->leagueId}.play"),
        ];

        if ($this->leagueId > 0) {
            $channels[] = new PrivateChannel("league.{$this->leagueId}.devices");
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'play.result.submitted';
    }
}
