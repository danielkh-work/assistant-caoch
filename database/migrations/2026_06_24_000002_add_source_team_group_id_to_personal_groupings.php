<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_groupings', function (Blueprint $table) {
            $table->unsignedBigInteger('source_team_group_id')
                ->nullable()
                ->after('roster_repair_player_ids');

            $table->foreign('source_team_group_id')
                ->references('id')
                ->on('team_groups')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('personal_groupings', function (Blueprint $table) {
            $table->dropForeign(['source_team_group_id']);
            $table->dropColumn('source_team_group_id');
        });
    }
};
