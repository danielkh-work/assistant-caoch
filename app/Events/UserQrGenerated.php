<?php

namespace App\Events;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserQrGenerated implements ShouldBroadcast
{ 
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $qrCode;
    public $userId;

    public function __construct(User $user)
    {
        $this->userId = $user->id;
        $this->qrCode = $user->qr_code; // only the qr_code
    }

      public function broadcastOn()
    {
        return new PrivateChannel("headcoach.{$this->userId}"); 
    }

    public function broadcastAs()
    {
        return 'user.register';
    }

    public function broadcastWith()
    {
        return [
            
            'user_id' => $this->userId,
            'qr_code' => $this->qrCode, // only sending the qr code
        ];
    }
}



