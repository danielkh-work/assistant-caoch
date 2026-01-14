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
        Schema::table('players_and_team_players_tables', function (Blueprint $table) {
            Schema::table('players', function (Blueprint $table) {
                $table->string('rpp')->nullable();
            });

            Schema::table('team_players', function (Blueprint $table) {
                $table->string('rpp')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players_and_team_players_tables', function (Blueprint $table) {
                Schema::table('players', function (Blueprint $table) {
                    $table->dropColumn('rpp');
                });

                Schema::table('team_players', function (Blueprint $table) {
                    $table->dropColumn('rpp');
                });
        });
    }
};





