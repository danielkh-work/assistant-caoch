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

    public $sessionId;
    public $mobileUserId;

    public function __construct(MobileSession $session)
    {
        $this->sessionId = $session->session_id;
        $this->mobileUserId = $session->mobile_user_id;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("mobile.{$this->mobileUserId}");
    }

    public function broadcastAs()
    {
        return 'session.approved';
    }

    public function broadcastWith()
    {
        return [
            'session_id' => $this->sessionId,
        ];
    }
}
