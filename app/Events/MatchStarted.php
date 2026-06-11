<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $leagueId;

    public $type;

    /** @var array<string, mixed>|string */
    public $scope;

    /**
     * @param  array<string, mixed>|string  $scope  Match context, e.g. practice vs real/play mode and game_id.
     */
    public function __construct($leagueId, $type = 'started', $scope = [])
    {
        $this->leagueId = $leagueId;
        $this->type = $type;
        $this->scope = $scope;
    }

    public function broadcastOn()
    {
        return [
            new PresenceChannel('league.global'),
            new PresenceChannel('league.'.$this->leagueId),
        ];
    }

    public function broadcastAs()
    {
        return 'match.'.$this->type;
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizedScope(): array
    {
        if (is_array($this->scope)) {
            return $this->scope;
        }

        if (is_string($this->scope) && $this->scope !== '') {
            return [
                'mode' => $this->scope,
                'is_play_mode' => $this->scope === 'real',
            ];
        }

        return [
            'mode' => 'practice',
            'is_play_mode' => false,
        ];
    }

    public function broadcastWith()
    {
        return [
            'league_id' => $this->leagueId,
            'type' => $this->type,
            'scope' => $this->normalizedScope(),
            'message' => $this->type === 'started'
                ? 'Match has started'
                : 'Match has ended',
        ];
    }
}
