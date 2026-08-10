<?php

namespace App\Console\Commands;

use App\Models\Configuration;
use App\Support\ActiveGameModeGuard;
use Illuminate\Console\Command;

class ExpireStaleGameModeSessions extends Command
{
    private const CONFIG_KEY = 'ENABLE_AUTO_ENDED_INACTIVE_MATCHES';

    protected $signature = 'game-modes:expire-stale
                            {--coach= : Optional head coach user id}
                            {--league= : Optional league id}
                            {--mode= : Optional mode filter: play or practice}';

    protected $description = 'Expire abandoned live match/practice sessions after regulation ends and inactivity timeout';

    public function handle(): int
    {
        $enabled = Configuration::query()
            ->where('key', self::CONFIG_KEY)
            ->value('value');

        if ($enabled !== 'true') {
            return self::SUCCESS;
        }

        $mode = $this->option('mode');

        if ($mode !== null && ! in_array($mode, ['play', 'practice'], true)) {
            $this->error('--mode must be play or practice.');

            return self::FAILURE;
        }

        ActiveGameModeGuard::expireStaleSessions(
            $this->option('coach') ? (int) $this->option('coach') : null,
            $mode,
            $this->option('league') ? (int) $this->option('league') : null,
        );

        return self::SUCCESS;
    }
}
