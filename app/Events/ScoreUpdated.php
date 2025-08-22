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
   protected $userId;

   public function __construct($scores,$userId)
   {
     $this->scores = $scores;
     $this->userId = $userId;
    //   $this->leagueId = $leagueId;
   }
    public function broadcastOn()
    {
        // return new PrivateChannel("user.{$this->userId}.league.{$this->leagueId}");
        \Log::info(['data'=>$this->userId]);
        return new PrivateChannel('user.' . $this->userId);
       
    }

    public function broadcastAs()
    {
        return 'score.updated';
    }
}
