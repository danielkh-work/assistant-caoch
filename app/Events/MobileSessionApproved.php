<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MobileSessionApproved implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    /** @var array<string, mixed> */
    public array $user;

    public function __construct(array $user)
    {
        $this->user = $user;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('qb-user.'.$this->user['user']['session_id']);
    }

    public function broadcastAs(): string
    {
        return 'session.approved';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $data = $this->user;
        $id = $data['user']['id'] ?? $data['user_id'] ?? null;
        if ($id !== null) {
            $data['user_id'] = $id;
        }

        return $data;
    }
}
