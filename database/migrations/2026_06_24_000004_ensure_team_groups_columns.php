<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'league_id'        => "ALTER TABLE `team_groups` ADD COLUMN `league_id` BIGINT UNSIGNED NULL",
            'group_name'       => "ALTER TABLE `team_groups` ADD COLUMN `group_name` VARCHAR(255) NOT NULL DEFAULT ''",
            'description'      => "ALTER TABLE `team_groups` ADD COLUMN `description` TEXT NULL",
            'type'             => "ALTER TABLE `team_groups` ADD COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'offense'",
            'players'          => "ALTER TABLE `team_groups` ADD COLUMN `players` JSON NULL",
            'practice_players' => "ALTER TABLE `team_groups` ADD COLUMN `practice_players` JSON NULL",
            'group_level'      => "ALTER TABLE `team_groups` ADD COLUMN `group_level` TINYINT UNSIGNED NOT NULL DEFAULT 11",
            'status'           => "ALTER TABLE `team_groups` ADD COLUMN `status` VARCHAR(32) NOT NULL DEFAULT 'active'",
        ];

        foreach ($columns as $col => $sql) {
            if (!Schema::hasColumn('team_groups', $col)) {
                DB::statement($sql);
            }
        }

        // Add foreign key for league_id only if column now exists and FK doesn't
        if (Schema::hasColumn('team_groups', 'league_id')) {
            $fkExists = DB::select("
                SELECT COUNT(*) as cnt
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'team_groups'
                  AND COLUMN_NAME = 'league_id'
                  AND REFERENCED_TABLE_NAME = 'leagues'
            ");
            if (($fkExists[0]->cnt ?? 0) === 0) {
                DB::statement("
                    ALTER TABLE `team_groups`
                    ADD CONSTRAINT `team_groups_league_id_foreign`
                    FOREIGN KEY (`league_id`) REFERENCES `leagues`(`id`) ON DELETE SET NULL
                ");
            }
        }
    }

    public function down(): void
    {
        // intentionally empty — columns were added to fix missing state
    }
};
