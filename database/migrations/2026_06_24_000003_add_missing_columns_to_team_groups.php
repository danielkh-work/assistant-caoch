<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('team_groups', 'league_id')) {
                $table->unsignedBigInteger('league_id')->nullable();
                $table->foreign('league_id')->references('id')->on('leagues')->onDelete('set null');
            }

            if (!Schema::hasColumn('team_groups', 'group_name')) {
                $table->string('group_name', 255)->default('');
            }

            if (!Schema::hasColumn('team_groups', 'description')) {
                $table->text('description')->nullable();
            }

            if (!Schema::hasColumn('team_groups', 'type')) {
                $table->string('type', 50)->default('offense');
            }

            if (!Schema::hasColumn('team_groups', 'players')) {
                $table->json('players')->nullable();
            }

            if (!Schema::hasColumn('team_groups', 'practice_players')) {
                $table->json('practice_players')->nullable();
            }

            if (!Schema::hasColumn('team_groups', 'group_level')) {
                $table->unsignedTinyInteger('group_level')->default(11);
            }

            if (!Schema::hasColumn('team_groups', 'status')) {
                $table->string('status', 32)->default('active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('team_groups', function (Blueprint $table) {
            $cols = ['league_id', 'group_name', 'description', 'type', 'players', 'practice_players', 'group_level', 'status'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('team_groups', $col)) {
                    if ($col === 'league_id') {
                        $table->dropForeign(['league_id']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
