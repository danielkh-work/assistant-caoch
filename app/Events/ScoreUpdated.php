<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Support\ScoreboardBroadcastPayload;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScoreUpdated implements ShouldBroadcast
{
   use Dispatchable, InteractsWithSockets, SerializesModels;

   public $data;
   protected $userId;
   protected $gameId;
   protected ?int $leagueId;


   public function __construct($data,$userId,$gameId, ?int $leagueId = null)
   {
     $this->data = $data;
     $this->userId = $userId;
     $this->gameId = $gameId;
     $this->leagueId = $leagueId;

   }
    public function broadcastOn()
    {
           \Log::info([' in sockeet before all request data'=>$this->data]);

         $channels = [
             new PrivateChannel("user.{$this->userId}.game.{$this->gameId}"),
         ];

         if ($this->leagueId && $this->leagueId > 0) {
             $channels[] = new PrivateChannel("league.{$this->leagueId}.devices");
         }

         return $channels;

    }

    public function broadcastAs()
    {
        return 'score.updated';
    }

    public function broadcastWith()
    {
        $data = $this->data;

        if (is_object($data)) {
            $data = (array) $data;
        }

        return ScoreboardBroadcastPayload::enrichTeamNames($data);
    }
}
