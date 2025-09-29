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
        Schema::create('penalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->onDelete('cascade');
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->string('penalty_type_id')->nullable();
            $table->string('category')->nullable();
            $table->string('severity')->nullable();
            $table->string('yardage_penalty')->nullable();
            $table->string('automatic_first_down')->nullable();
            $table->string('loss_down')->nullable();
            $table->string('accept_reject')->nullable();
            $table->string('replay_down')->nullable();
            $table->string('new_down')->nullable();
            $table->string('new_ball_sport')->nullable();
            $table->string('play_time')->nullable();
            $table->string('setuation')->nullable();
            $table->string('referee')->nullable();
            $table->string('notes_description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penalities');
    }
};
