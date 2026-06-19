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
    protected ?int $deviceId;

    public function __construct(array $log, int $userId, int $gameId, ?int $deviceId = null)
    {
        $this->log      = $log;
        $this->userId   = $userId;
        $this->gameId   = $gameId;
        $this->deviceId = $deviceId;
    }

    public function broadcastOn(): PrivateChannel
    {
        // If a device is configured, broadcast to device-specific channel
        // Otherwise, fall back to user-specific channel for backward compatibility
        if ($this->deviceId) {
            return new PrivateChannel("device.{$this->deviceId}.game.{$this->gameId}");
        }

        return new PrivateChannel("user.{$this->userId}.game.{$this->gameId}");
    }

    public function broadcastAs(): string
    {
        return 'match.log.created';
    }
}
