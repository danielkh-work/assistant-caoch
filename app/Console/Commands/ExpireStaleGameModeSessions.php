<?php

namespace App\Console\Commands;

use App\Support\ActiveGameModeGuard;
use Illuminate\Console\Command;

class ExpireStaleGameModeSessions extends Command
{
    protected $signature = 'game-modes:expire-stale
                            {--coach= : Optional head coach user id}
                            {--league= : Optional league id}
                            {--mode= : Optional mode filter: play or practice}';

    protected $description = 'Expire abandoned live match/practice sessions after the inactivity timeout';

    public function handle(): int
    {
        $mode = $this->option('mode');

        if ($mode !== null && ! in_array($mode, ['play', 'practice'], true)) {
            $this->error('--mode must be play or practice.');

            return self::FAILURE;
        }

        $expired = ActiveGameModeGuard::expireStaleSessions(
            $this->option('coach') ? (int) $this->option('coach') : null,
            $mode,
            $this->option('league') ? (int) $this->option('league') : null,
        );

        $this->info("Expired stale live sessions: {$expired}");

        return self::SUCCESS;
    }
}
