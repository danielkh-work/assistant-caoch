<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlaySuggested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

 
    public $play;

    public function __construct($play)
    {
        $this->play = $play;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('headcoach.' . $this->play['head_coach_id'] . '.qb');
    }

    public function broadcastAs()
    {
        return 'play.suggested';
    }
}
