<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameLogAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $type;
    public $game_id;

    protected $coachGroupId;

    public function __construct(int $coachGroupId, int $gameId)
    {
        $this->coachGroupId = $coachGroupId;
        $this->game_id = $gameId;
        $this->type = 'log_added';
    }

    public function broadcastOn()
    {
        return new PrivateChannel("user.{$this->coachGroupId}.game.{$this->game_id}");
    }

    public function broadcastAs()
    {
        return 'log.added';
    }
}
