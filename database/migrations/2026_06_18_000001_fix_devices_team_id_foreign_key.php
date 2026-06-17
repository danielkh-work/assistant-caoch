<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('devices') || ! Schema::hasTable('league_teams')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            $table->integer('team_id')->nullable()->change();
        });

        $existingFk = DB::selectOne(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'devices'
               AND COLUMN_NAME = 'team_id'
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1"
        );

        if ($existingFk?->CONSTRAINT_NAME) {
            Schema::table('devices', function (Blueprint $table) use ($existingFk) {
                $table->dropForeign($existingFk->CONSTRAINT_NAME);
            });
        }

        $leagueTeamsFk = DB::selectOne(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'devices'
               AND COLUMN_NAME = 'team_id'
               AND REFERENCED_TABLE_NAME = 'league_teams'
             LIMIT 1"
        );

        if (! $leagueTeamsFk) {
            Schema::table('devices', function (Blueprint $table) {
                $table->foreign('team_id')
                    ->references('id')
                    ->on('league_teams')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('devices')) {
            return;
        }

        $existingFk = DB::selectOne(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'devices'
               AND COLUMN_NAME = 'team_id'
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1"
        );

        if ($existingFk?->CONSTRAINT_NAME) {
            Schema::table('devices', function (Blueprint $table) use ($existingFk) {
                $table->dropForeign($existingFk->CONSTRAINT_NAME);
            });
        }
    }
};
