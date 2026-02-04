<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class YardageBroadcast  implements ShouldBroadcast
{
   use Dispatchable, InteractsWithSockets, SerializesModels;

   public $data;
   protected $userId;
  

   public function __construct($data,$userId)
   {
     $this->data = $data;
     $this->userId = $userId;
   }
    public function broadcastOn()
    {
         return new PrivateChannel("coach-group.{$this->userId}");
      
    }

    public function broadcastAs()
    {
        return 'assistant.coaches';
    }
}
