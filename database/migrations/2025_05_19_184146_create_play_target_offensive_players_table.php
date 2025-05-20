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
        Schema::create('play_target_offensive_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('play_id')->constrained()->onDelete('cascade');
            $table->foreignId('offensive_position_id')->constrained()->onDelete('cascade');
            $table->integer('strength')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('play_target_offensive_players');
    }
};
