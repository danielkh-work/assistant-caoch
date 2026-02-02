<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PracticeScoreUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

   public $scores;
   protected $userId;
   protected $gameId;
   public function __construct($scores,$userId,$gameId)
   {
     $this->scores = $scores;
     $this->userId = $userId;
     $this->gameId = $gameId;
    //   $this->leagueId = $leagueId;
    }
    public function broadcastOn()
    {
        // Make the channel unique per user per game
    \Log::info('Broadcasting ScoreUpdated event', [
        'userId' => $this->userId,
        'gameId' => $this->gameId,
    ]);
        return new PrivateChannel("user.{$this->userId}.game.{$this->gameId}");
    }

    public function broadcastAs()
    {
           return 'score.updated';
        // return 'practice.score.updated';
    }

}
