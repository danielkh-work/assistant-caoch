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
        Schema::create('configure_defensive_plays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('play_id')->constrained('defensive_plays')->onDelete('cascade');
            $table->unsignedBigInteger('league_id')->constrained('leagues')->onDelete('cascade');
            $table->unsignedBigInteger('game_id')->constrained('games')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configure_defensive_plays');
    }
};
