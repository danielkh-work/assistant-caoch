<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScoreUpdated implements ShouldBroadcast
{
   use Dispatchable, InteractsWithSockets, SerializesModels;

   public $scores;

   public function __construct($scores)
   {
     $this->scores = $scores;
   }
    public function broadcastOn()
    {
        return new Channel('scoreboard');
    }

    public function broadcastAs()
    {
        return 'score.updated';
    }
}
