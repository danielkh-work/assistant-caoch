<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyGroupsToTeamLevel extends Command
{
    protected $signature = 'groups:migrate-legacy
                            {--dry-run : Preview what would be migrated without writing}';

    protected $description = 'Migrate legacy game-level personal_groupings to team-level team_groups';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY RUN] No changes will be written.');
        }

        // Load legacy groups that are NOT already-imported copies
        $rows = DB::select('
            SELECT id, game_id, team_id, league_id, group_name, type,
                   players, practice_players, status, group_level
            FROM personal_groupings
            WHERE source_team_group_id IS NULL
            ORDER BY team_id, group_name, id DESC
        ');

        if (empty($rows)) {
            $this->info('No legacy groups found. Nothing to migrate.');
            return 0;
        }

        // Deduplicate by (team_id, group_name): keep the row with most players,
        // then highest id as tiebreaker.
        $canonical = [];
        foreach ($rows as $row) {
            $key = $row->team_id . '|' . $row->group_name;
            $playerCount  = $row->players         ? count(json_decode($row->players, true) ?? [])          : 0;
            $ppCount      = $row->practice_players ? count(json_decode($row->practice_players, true) ?? []) : 0;
            $total        = $playerCount + $ppCount;

            if (!isset($canonical[$key])) {
                $canonical[$key] = ['row' => $row, 'total' => $total];
            } else {
                $existing = $canonical[$key];
                if ($total > $existing['total'] || ($total === $existing['total'] && $row->id > $existing['row']->id)) {
                    $canonical[$key] = ['row' => $row, 'total' => $total];
                }
            }
        }

        $this->info(sprintf(
            'Found %d legacy groups across unique (team, group_name) combinations (from %d raw rows).',
            count($canonical),
            count($rows)
        ));

        $migrated = 0;
        $skipped  = 0;

        foreach ($canonical as $key => $entry) {
            $row   = $entry['row'];
            $total = $entry['total'];

            // Skip if already exists in team_groups for this (team_id, group_name)
            $exists = DB::table('team_groups')
                ->where('team_id', $row->team_id)
                ->where('group_name', $row->group_name)
                ->exists();

            if ($exists) {
                $this->line(sprintf(
                    '  SKIP  team=%d group="%s" — already in team_groups',
                    $row->team_id, $row->group_name
                ));
                $skipped++;
                continue;
            }

            // Map status: NULL → 'draft', else keep as-is ('active' / 'inactive')
            $status = $row->status ?? 'draft';

            $this->line(sprintf(
                '  %s  team=%d group="%s" type=%s status=%s players=%d',
                $dryRun ? 'WOULD INSERT' : 'INSERT',
                $row->team_id,
                $row->group_name,
                $row->type ?? '?',
                $status,
                $total
            ));

            if (!$dryRun) {
                DB::table('team_groups')->insert([
                    'team_id'          => $row->team_id,
                    'league_id'        => $row->league_id,
                    'group_name'       => $row->group_name,
                    'description'      => null,
                    'type'             => $row->type,
                    'players'          => $row->players,
                    'practice_players' => $row->practice_players,
                    'group_level'      => $row->group_level,
                    'status'           => $status,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            $migrated++;
        }

        $this->newLine();
        $this->info(sprintf(
            'Done. Migrated: %d | Skipped (already exist): %d',
            $migrated,
            $skipped
        ));

        return 0;
    }
}
