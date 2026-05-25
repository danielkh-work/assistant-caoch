<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchLogCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $log;
    protected int $userId;
    protected int $gameId;

    public function __construct(array $log, int $userId, int $gameId)
    {
        $this->log    = $log;
        $this->userId = $userId;
        $this->gameId = $gameId;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("user.{$this->userId}.game.{$this->gameId}");
    }

    public function broadcastAs(): string
    {
        return 'match.log.created';
    }
}
