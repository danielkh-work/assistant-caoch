<?php

namespace App\Console\Commands;

use App\Models\Player;
use App\Services\PlayerPurgeService;
use Illuminate\Console\Command;

class CascadeDeleteTestPlayers extends Command
{
    protected $signature = 'players:cascade-delete-test
                            {--ids= : Comma-separated players.id values (default: all test-name matches)}
                            {--preview : Preview what will be deleted without making changes}
                            {--confirm-delete : Soft-delete the selected players and related data}';

    protected $description = 'Soft-delete test players and scrub related roster, grouping, and log references';

    public function handle(PlayerPurgeService $service): int
    {
        $preview = (bool) $this->option('preview');
        $confirmDelete = (bool) $this->option('confirm-delete');

        if ($preview && $confirmDelete) {
            $this->error('Use either --preview or --confirm-delete, not both.');

            return self::FAILURE;
        }

        if (! $preview && ! $confirmDelete) {
            $this->error('Pass --preview to inspect changes or --confirm-delete to apply them.');

            return self::FAILURE;
        }

        $playerIds = $this->resolvePlayerIds();

        if ($playerIds === []) {
            $this->info('No matching test players found.');
            $this->comment('Run php artisan players:select-test to preview name matches.');

            return self::SUCCESS;
        }

        $this->line('Player ids: '.implode(', ', $playerIds));

        $players = Player::query()->whereIn('id', $playerIds)->orderBy('id')->get(['id', 'name', 'number']);
        if ($players->isNotEmpty()) {
            $this->newLine();
            $this->table(
                ['id', 'name', 'number'],
                $players->map(fn ($player) => [$player->id, $player->name, $player->number])->all()
            );
        }

        if ($preview) {
            $this->warn('Preview only — no rows will be changed.');
        } else {
            $this->warn('This will soft-delete rows (sets deleted_at), not hard-delete them.');
        }

        $stats = $service->purgeByPlayerIds($playerIds, $preview);

        $this->newLine();
        $this->info($preview ? 'Preview complete.' : 'Soft-delete complete.');
        $this->table(
            ['Key', 'Value'],
            collect($stats)
                ->except(['player_ids', 'team_player_ids', 'practice_player_ids'])
                ->flatMap(function ($value, $key) {
                    if ($key === 'dry_run') {
                        return [];
                    }

                    if (! is_array($value)) {
                        return [[$key, (string) $value]];
                    }

                    return collect($value)->map(fn ($count, $subKey) => ["{$key}.{$subKey}", (string) $count])->all();
                })
                ->all()
        );

        $this->line('Team player ids: '.implode(', ', $stats['team_player_ids'] ?: ['none']));
        $this->line('Practice player ids: '.implode(', ', $stats['practice_player_ids'] ?: ['none']));

        if ($preview) {
            $this->newLine();
            $this->comment('To soft-delete, run the same command with --confirm-delete instead of --preview.');
        }

        return self::SUCCESS;
    }

    /** @return array<int,int> */
    private function resolvePlayerIds(): array
    {
        $idsOption = $this->option('ids');

        if ($idsOption) {
            return array_values(array_filter(array_map('intval', explode(',', (string) $idsOption))));
        }

        return PlayerPurgeService::testPlayerQuery()
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
