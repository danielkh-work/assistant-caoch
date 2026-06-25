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

        // Convert object to array if needed
        if (is_object($data)) {
            $data = (array) $data;
        }

        // Add team names inside scores structure if not already present
        if (isset($data['scores']) && isset($data['game_id'])) {
            if (!isset($data['scores']['left']['name']) || !isset($data['scores']['right']['name'])) {
                $game = \App\Models\PlayGameMode::find($data['game_id']);
                if ($game) {
                    if (!isset($data['scores']['left']['name']) && $game->myTeam) {
                        $data['scores']['left']['name'] = $game->myTeam->team_name;
                    }
                    if (!isset($data['scores']['right']['name']) && $game->opponentTeam) {
                        $data['scores']['right']['name'] = $game->opponentTeam->team_name;
                    }
                }
            }
        }

        return $data;
    }
}
