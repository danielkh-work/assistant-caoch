<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // personal_groupings.group_level was 1/2 (game/practice mode flag), not player count.
        // Our migrate-legacy command copied it as-is, so team_groups ended up with group_level=2
        // instead of the correct 7/11/12. Recalculate from actual player JSON.
        $rows = DB::table('team_groups')
            ->whereNotIn('group_level', [7, 11, 12])
            ->get(['id', 'players', 'practice_players']);

        foreach ($rows as $row) {
            $data  = json_decode($row->players ?? $row->practice_players ?? '[]', true) ?? [];
            $count = count($data);

            if ($count >= 12) {
                $level = 12;
            } elseif ($count >= 8) {
                $level = 11;
            } else {
                $level = 7;
            }

            DB::table('team_groups')
                ->where('id', $row->id)
                ->update(['group_level' => $level, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Not reversible.
    }
};
