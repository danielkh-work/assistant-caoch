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
         try {
         Schema::table('offense_defense_players', function (Blueprint $table) {
            $table->unsignedBigInteger('player_id')->nullable()->change();
            $table->unsignedBigInteger('practice_player_id')->nullable()->after('player_id');

        });
         } catch (\Illuminate\Database\QueryException $e) {
             if (stripos($e->getMessage(), 'Duplicate') === false && stripos($e->getMessage(), 'already exists') === false) throw $e;
         }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
        Schema::table('offense_defense_players', function (Blueprint $table) {
            $table->dropColumn('practice_player_id');
            $table->unsignedBigInteger('player_id')->nullable(false)->change();
        });
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'Duplicate') === false && stripos($e->getMessage(), 'already exists') === false) throw $e;
        }
    }
};
