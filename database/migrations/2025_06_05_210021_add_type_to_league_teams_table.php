<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('league_teams', 'type')) {
            Schema::table('league_teams', function (Blueprint $table) {
                $table->integer('type')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('league_teams', 'type')) {
            Schema::table('league_teams', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
