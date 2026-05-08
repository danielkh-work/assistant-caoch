<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_groupings', function (Blueprint $table) {
            if (!Schema::hasColumn('personal_groupings', 'status')) {
                $table->string('status', 32)->default('active')->after('group_level');
            }
            if (!Schema::hasColumn('personal_groupings', 'roster_repair_player_ids')) {
                $table->json('roster_repair_player_ids')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('personal_groupings', function (Blueprint $table) {
            if (Schema::hasColumn('personal_groupings', 'roster_repair_player_ids')) {
                $table->dropColumn('roster_repair_player_ids');
            }
            if (Schema::hasColumn('personal_groupings', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
