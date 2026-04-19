<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $leagueId;
    public $type;
    public $scope;
    public function __construct($leagueId , $type = 'started', $scope)
    {
        $this->leagueId = $leagueId;
        $this->type = $type;
        $this->scope = $scope;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('league.' . $this->leagueId);
    }

     public function broadcastAs()
    {
        return 'match.' . $this->type; // 🔥 IMPORTANT
    }

    public function broadcastWith()
    {
        return [
            'league_id' => $this->leagueId,
            'scope' => $this->scope,
            'message' => $this->type === 'started'
                ? 'Match has started'
                : 'Match has ended'
        ];
    }
}
