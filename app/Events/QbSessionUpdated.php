<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the head coach dashboard when a QB mobile session starts or ends.
 * Listened on private channel headcoach.{headCoachId}.league.{leagueId}.qb as .qb.session.updated
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
        return [
            new PrivateChannel('headcoach.'.$this->headCoachId.'.league.'.$this->leagueId.'.qb'),
        ];
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
            'user' => $this->user,
        ];
    }
}
