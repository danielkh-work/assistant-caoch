<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerSubstituted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $gameId;
    public $teamId;
    protected $headCoachId;

    public function __construct($headCoachId, $gameId, $teamId)
    {
        $this->headCoachId = $headCoachId;
        $this->gameId      = $gameId;
        $this->teamId      = $teamId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("user.{$this->headCoachId}.game.{$this->gameId}");
    }

    public function broadcastAs()
    {
        return 'player.substituted';
    }
}
