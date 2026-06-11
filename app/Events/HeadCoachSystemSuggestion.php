<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HeadCoachSystemSuggestion implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $data;

    protected int $headCoachId;

    public function __construct(array $data, int $headCoachId)
    {
        $this->data = $data;
        $this->headCoachId = $headCoachId;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("coach-group.{$this->headCoachId}");
    }

    public function broadcastAs(): string
    {
        return 'head.coach.suggestion';
    }
}
