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
   protected ?int $leagueId;

   public function __construct($scores,$userId,$gameId, ?int $leagueId = null)
   {
     $this->scores = $scores;
     $this->userId = $userId;
     $this->gameId = $gameId;
     $this->leagueId = $leagueId;
    }
    public function broadcastOn()
    {
    \Log::info('Broadcasting ScoreUpdated event', [
        'userId' => $this->userId,
        'gameId' => $this->gameId,
    ]);

        $channels = [
            new PrivateChannel("user.{$this->userId}.practice.{$this->gameId}"),
        ];

        if ($this->leagueId && $this->leagueId > 0) {
            $channels[] = new PrivateChannel("league.{$this->leagueId}.devices");
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'practice.score.updated';
    }

}
