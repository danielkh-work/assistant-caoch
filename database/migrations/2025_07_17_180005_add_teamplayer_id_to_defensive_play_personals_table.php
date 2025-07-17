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
        Schema::table('defensive_play_personals', function (Blueprint $table) {
            $table->unsignedBigInteger('teamplayer_id')->nullable()->after('id');
            $table->foreign('teamplayer_id')->references('id')->on('team_players')->onDelete('set null');
        });
    }

   
    public function down(): void
    {
        Schema::table('defensive_play_personals', function (Blueprint $table) {
            $table->dropForeign(['teamplayer_id']);
            $table->dropColumn('teamplayer_id');
        });
    }
};
