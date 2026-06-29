<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MobileSessionLogout implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    /** @var array<string, mixed> */
    public array $user;

    public string $sessionId;

    /**
     * @param  array<string, mixed>  $user
     */
    public function __construct(array $user, string $sessionId)
    {
        $this->user = $user;
        $this->sessionId = $sessionId;
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("qb-logout.{$this->sessionId}"),
            new Channel("qb-user.{$this->sessionId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'session.logout';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->user;
    }
}
