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
    protected $isPractice;

    public function __construct($headCoachId, $gameId, $teamId, $isPractice = false)
    {
        $this->headCoachId = $headCoachId;
        $this->gameId      = $gameId;
        $this->teamId      = $teamId;
        $this->isPractice  = (bool) $isPractice;
    }

    public function broadcastOn()
    {
        $namespace = $this->isPractice ? 'practice' : 'game';
        return new PrivateChannel("user.{$this->headCoachId}.{$namespace}.{$this->gameId}");
    }

    public function broadcastAs()
    {
        return 'player.substituted';
    }
}
