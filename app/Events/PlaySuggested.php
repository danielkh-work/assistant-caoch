<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlaySuggested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $play;

    public $coachGroupId;

    public int $leagueId;

    public function __construct($play, $coachGroupId, int $leagueId)
    {
        $this->play = $play;
        $this->coachGroupId = $coachGroupId;
        $this->leagueId = $leagueId;
        \Log::info(['plays' => $this->play]);
        \Log::info(['coachGroupId' => $this->coachGroupId]);
    }

    public function broadcastOn()
    {
        return new PrivateChannel("headcoach.{$this->coachGroupId}.league.{$this->leagueId}.play");
    }

    public function broadcastAs()
    {
        return 'play.suggested';
    }
}
