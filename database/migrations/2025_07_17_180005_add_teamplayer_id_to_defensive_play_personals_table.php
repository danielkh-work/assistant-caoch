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
        Schema::table('defensive_play_personals', function (Blueprint $table) {
            $table->unsignedBigInteger('teamplayer_id')->nullable()->after('id');
            $table->foreign('teamplayer_id')->references('id')->on('team_players')->onDelete('set null');
        });
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'Duplicate') === false && stripos($e->getMessage(), 'already exists') === false) throw $e;
        }
    }

   
    public function down(): void
    {
        try {
        Schema::table('defensive_play_personals', function (Blueprint $table) {
            $table->dropForeign(['teamplayer_id']);
            $table->dropColumn('teamplayer_id');
        });
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'Duplicate') === false && stripos($e->getMessage(), 'already exists') === false) throw $e;
        }
    }
};
