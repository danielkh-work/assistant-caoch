<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameRestored implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Game $game) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('league.'.$this->game->league_id);
    }

    public function broadcastAs(): string
    {
        return 'game.restored';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'game' => [
                'id' => $this->game->id,
                'league_id' => $this->game->league_id,
                'status' => $this->game->status,
                'match_start_date' => $this->game->match_start_date,
                'match_end_date' => $this->game->match_end_date,
            ],
        ];
    }
}
