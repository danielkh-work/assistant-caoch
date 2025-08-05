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
        Schema::create('opponent_package_player', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opponent_team_package_id')->constrained()->onDelete('cascade');
              $table->foreignId('player_id')
              ->constrained('team_players') // 👈 explicitly reference team_players
              ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opponent_package_player');
    }
};
