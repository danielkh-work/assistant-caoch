<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the head coach dashboard when a QB mobile session starts or ends.
 * Broadcasts on legacy headcoach.{headCoachId}.qb (mobile app) and
 * headcoach.{headCoachId}.league.{leagueId}.qb (league-scoped web).
 */
class QbSessionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $user  QB user fields (id, name, session_id, code, league_id, …)
     */
    public function __construct(
        public int $headCoachId,
        public int $leagueId,
        public array $user,
        public bool $isLoggedIn,
        public string $action,
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('headcoach.'.$this->headCoachId.'.qb'),
        ];

        if ($this->leagueId > 0) {
            $channels[] = new PrivateChannel(
                'headcoach.'.$this->headCoachId.'.league.'.$this->leagueId.'.qb'
            );
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'qb.session.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'is_loggin' => $this->isLoggedIn,
            'league_id' => $this->leagueId,
            'team_id' => $this->user['team_id'] ?? null,
            'user' => $this->user,
        ];
    }
}
