<?php

namespace App\Console\Commands;

use App\Support\ActiveGameModeGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EndActiveGameModes extends Command
{
    protected $signature = 'game-modes:end
                            {--coach= : Head coach user id (ends both play and practice for this coach)}
                            {--league= : Optional league id filter}
                            {--all : End all active game and practice sessions}';

    protected $description = 'End active game mode and practice mode sessions (DB + scoreboard rows)';

    public function handle(): int
    {
        $coachId = $this->option('coach');
        $leagueId = $this->option('league');
        $all = (bool) $this->option('all');

        if (! $all && ! $coachId) {
            $this->error('Provide --coach=ID or --all');

            return self::FAILURE;
        }

        $sessionQuery = DB::table('play_game_modes')->where('status', ActiveGameModeGuard::STATUS_ACTIVE);

        if ($coachId) {
            $sessionQuery->where('user_id', (int) $coachId);
        }

        if ($leagueId) {
            $sessionQuery->where('league_id', (int) $leagueId);
        }

        $sessionsEnded = (clone $sessionQuery)->update([
            'status' => ActiveGameModeGuard::STATUS_COMPLETED,
            'updated_at' => now(),
        ]);

        $gameScoreboardQuery = DB::table('websocket_scoreboards')->where('is_start', true);
        $practiceScoreboardQuery = DB::table('websocket_practice_scoreboards')->where('is_start', true);

        if ($coachId) {
            $gameScoreboardQuery->where('user_id', (int) $coachId);
            $practiceScoreboardQuery->where('user_id', (int) $coachId);
        }

        if ($leagueId) {
            $gameScoreboardQuery->where('league_id', (int) $leagueId);
            $practiceScoreboardQuery->where('league_id', (int) $leagueId);
        }

        $scoreboardValues = [
            'is_start' => false,
            'action' => 'EndMatch',
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('websocket_scoreboards', 'sys_time')) {
            $scoreboardValues['sys_time'] = now()->toDateTimeString();
        }

        $gameScoreboardsEnded = $gameScoreboardQuery->update($scoreboardValues);
        $practiceScoreboardsEnded = $practiceScoreboardQuery->update($scoreboardValues);

        $this->info("Completed play_game_modes rows: {$sessionsEnded}");
        $this->info("Game scoreboards ended: {$gameScoreboardsEnded}");
        $this->info("Practice scoreboards ended: {$practiceScoreboardsEnded}");

        return self::SUCCESS;
    }
}
