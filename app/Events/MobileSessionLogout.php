<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\MobileSession;
use Illuminate\Broadcasting\Channel;

class MobileSessionLogout implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

     public $user;

    public function __construct(array $user)
    {
    
        $this->user = $user;
          \Log::info('log info', [
        'session_id' => $this->user['user']['session_id']
    ]);
    }


     public function broadcastOn()
    {
        
        return new Channel("qb-logout.{$this->user['user']['session_id']}");
    }

    public function broadcastAs()
    {
        return 'session.logout';
    }

    public function broadcastWith()
    {
        return $this->user;
    }
}
