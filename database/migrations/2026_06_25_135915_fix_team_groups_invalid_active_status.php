<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Force status to 'inactive' for any team_group marked 'active'
        // where the stored player count does not match the group_level.
        // This cleans up legacy-migrated or manually-created bad records.
        DB::statement("
            UPDATE team_groups
            SET status = 'inactive', updated_at = NOW()
            WHERE status = 'active'
              AND (
                  players IS NULL
                  OR JSON_LENGTH(players) != group_level
              )
        ");
    }

    public function down(): void
    {
        // Not reversible — status history is not tracked.
    }
};
