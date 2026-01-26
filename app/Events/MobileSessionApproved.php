<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\MobileSession;

class MobileSessionApproved implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

     public $user;

    public function __construct(array $user)
    {
        \Log::info(['mobile data qrcode user'=>$user]);
        $this->user = $user;
    }


     public function broadcastOn()
    {
        return new PrivateChannel('qb-user');
    }

    public function broadcastAs()
    {
        return 'session.approved';
    }

    public function broadcastWith()
    {
        return $this->user;
    }
}
