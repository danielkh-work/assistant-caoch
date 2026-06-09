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

    public int $headCoachId;

    public int $leagueId;

    public function __construct(array $data, int $headCoachId, int $leagueId)
    {
        $this->data = $data;
        $this->headCoachId = $headCoachId;
        $this->leagueId = $leagueId;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("coach-group.{$this->headCoachId}.league.{$this->leagueId}");
    }

    public function broadcastAs(): string
    {
        return 'head.coach.suggestion';
    }
}
