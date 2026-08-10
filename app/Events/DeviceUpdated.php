<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the head coach dashboard when device information changes (battery, signal strength, etc.).
 * Broadcasts on headcoach.{headCoachId}.league.{leagueId}.device.
 */
class DeviceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $device  Device fields including battery and signal strength
     */
    public function __construct(
        public int $headCoachId,
        public int $leagueId,
        public array $device,
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('headcoach.'.$this->headCoachId.'.league.'.$this->leagueId.'.device'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'device.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'device' => $this->device,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
