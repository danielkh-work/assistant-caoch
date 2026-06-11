<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class YardageBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;

    protected $userId;

    public int $leagueId;

    public function __construct($data, $userId, int $leagueId)
    {
        $this->data = $data;
        $this->userId = $userId;
        $this->leagueId = $leagueId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("coach-group.{$this->userId}.league.{$this->leagueId}");
    }

    public function broadcastAs()
    {
        return 'assistant.coaches';
    }
}
