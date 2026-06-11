<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamScoreUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $score;

    public $coachGroupId;

    public int $leagueId;

    public function __construct($score, $coachGroupId, int $leagueId)
    {
        \Log::info(['score' => $score]);
        $this->score = $score;
        $this->coachGroupId = $coachGroupId;
        $this->leagueId = $leagueId;
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("headcoach.{$this->coachGroupId}.qb"),
        ];

        if ($this->leagueId > 0) {
            $channels[] = new PrivateChannel(
                "headcoach.{$this->coachGroupId}.league.{$this->leagueId}.qb"
            );
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'team.score.updated';
    }
}
