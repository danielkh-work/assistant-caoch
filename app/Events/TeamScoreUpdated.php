<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class TeamScoreUpdated implements ShouldBroadcast
{
    use SerializesModels;
    public $score;
    public $coachGroupId;
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct($score, $coachGroupId)
    {
          
          
         $this->score = $score;
         $this->coachGroupId = $coachGroupId;

        
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
    //    {$this->coachGroupId}
        return new PrivateChannel("headcoach.{$this->coachGroupId}.qb");
        //return new PrivateChannel('headcoach.' . $this->coachGroupId . '.qb');
    }

     public function broadcastAs()
    {
        return 'team.score.updated';
    }
}

