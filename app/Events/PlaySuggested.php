<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlaySuggested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

 
    public $play;
    public $coachGroupId;

    public function __construct($play, $coachGroupId)
    {
        $this->play = $play;
        $this->coachGroupId = $coachGroupId;
        \Log::info(['plays'=>$this->play]);
        \Log::info(['coachGroupId'=> $this->coachGroupId]);
    }

    public function broadcastOn()
    {
        return new PrivateChannel("headcoach.{$this->coachGroupId}.play");
        
    }

    public function broadcastAs()
    {
        return 'play.suggested';
    }
}
