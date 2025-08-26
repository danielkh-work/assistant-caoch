<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent  implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct($message)
    {
         $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    //: array
    {
        
        return new \Illuminate\Broadcasting\Channel('chat');
        //  return new PrivateChannel('chat');
        // return [
        //     new PrivateChannel('channel-name'),
        // ];
    }
      public function broadcastAs()
    {
        return 'message.sent';
    }
}
