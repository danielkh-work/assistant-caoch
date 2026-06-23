<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('team_group_player');
        Schema::dropIfExists('team_groups');

        Schema::create('team_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 20)->index();
            $table->unsignedTinyInteger('segment')->index();
            $table->string('status', 20)->index()->default('draft');
            $table->timestamps();
        });

        Schema::create('team_group_player', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_group_id');
            $table->unsignedBigInteger('team_player_id');
            $table->timestamps();
            $table->unique(['team_group_id', 'team_player_id']);
        });

        if (! Schema::hasTable('personal_groupings')) {
            return;
        }

        $legacyGroups = DB::table('personal_groupings')->orderBy('id')->get();
        if ($legacyGroups->isEmpty()) {
            return;
        }

        $normalizeLegacyPlayers = function ($value): array {
            $rows = is_array($value) ? $value : (json_decode((string) $value, true) ?: []);
            $ids = [];

            foreach ($rows as $row) {
                if (is_int($row) || (is_string($row) && ctype_digit($row))) {
                    $ids[] = (int) $row;
                    continue;
                }

                if (! is_array($row) || ! isset($row['id'])) {
                    continue;
                }

                $ids[] = (int) $row['id'];
            }

            return array_values(array_unique(array_filter($ids)));
        };

        $deduped = [];

        foreach ($legacyGroups as $row) {
            $teamId = (int) $row->team_id;
            if (! $teamId || ! DB::table('league_teams')->where('id', $teamId)->exists()) {
                continue;
            }

            $segment = in_array((int) $row->group_level, [7, 11, 12], true)
                ? (int) $row->group_level
                : 11;

            $signature = implode('|', [
                $teamId,
                strtolower((string) ($row->group_name ?? '')),
                strtolower((string) ($row->type ?? 'offense')),
                $segment,
                strtolower((string) ($row->status ?? 'draft')),
            ]);

            $playerIds = $normalizeLegacyPlayers($row->players);
            $practiceIds = $normalizeLegacyPlayers($row->practice_players);

            if ($practiceIds !== []) {
                $mapped = DB::table('practice_team_players')
                    ->whereIn('id', $practiceIds)
                    ->pluck('player_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();

                $playerIds = array_values(array_unique(array_merge($playerIds, $mapped)));
            }

            $deduped[$signature] = [
                'team_id' => $teamId,
                'name' => (string) ($row->group_name ?? 'Group'),
                'description' => $row->league_id ? 'Migrated from game-scoped group #' . $row->id : null,
                'type' => strtolower((string) ($row->type ?? 'offense')),
                'segment' => $segment,
                'status' => in_array((string) ($row->status ?? 'draft'), ['active', 'inactive', 'draft'], true)
                    ? (string) $row->status
                    : 'draft',
                'players' => $playerIds,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];
        }

        foreach ($deduped as $groupData) {
            $playerIds = $groupData['players'];
            unset($groupData['players']);

            $groupId = DB::table('team_groups')->insertGetId($groupData);

            if ($playerIds !== []) {
                $pivotRows = array_map(fn ($playerId) => [
                    'team_group_id' => $groupId,
                    'team_player_id' => $playerId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $playerIds);

                DB::table('team_group_player')->insert($pivotRows);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('team_group_player');
        Schema::dropIfExists('team_groups');
    }
};
