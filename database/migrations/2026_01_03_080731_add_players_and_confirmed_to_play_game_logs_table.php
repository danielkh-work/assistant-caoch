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
        Schema::table('play_game_logs', function (Blueprint $table) {
            $table->json('players')->nullable();
            $table->boolean('confirmed')->nullable()->after('players');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('play_game_logs', function (Blueprint $table) {
             $table->dropColumn(['players', 'confirmed']);
        });
    }
};
