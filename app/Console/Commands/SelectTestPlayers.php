<?php

namespace App\Console\Commands;

use App\Services\PlayerPurgeService;
use Illuminate\Console\Command;

class SelectTestPlayers extends Command
{
    protected $signature = 'players:select-test
                            {--ids-only : Output comma-separated ids only}';

    protected $description = 'List players.id values matching test/junk name patterns (select_test_players_by_name.sql)';

    public function handle(): int
    {
        $players = PlayerPurgeService::testPlayerQuery()
            ->orderBy('id')
            ->get(['id', 'name', 'number', 'created_at']);

        if ($players->isEmpty()) {
            $this->info('No matching test players found.');

            return self::SUCCESS;
        }

        if ($this->option('ids-only')) {
            $this->line($players->pluck('id')->implode(','));

            return self::SUCCESS;
        }

        $this->table(
            ['id', 'name', 'number', 'created_at'],
            $players->map(fn ($player) => [
                $player->id,
                $player->name,
                $player->number,
                $player->created_at,
            ])->all()
        );

        $this->newLine();
        $this->line('Count: '.$players->count());
        $this->line('Ids: '.$players->pluck('id')->implode(', '));
        $this->newLine();
        $this->comment('Next: php artisan players:cascade-delete-test --preview');

        return self::SUCCESS;
    }
}
