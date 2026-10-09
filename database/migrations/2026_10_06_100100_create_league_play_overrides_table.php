<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('league_play_overrides', function (Blueprint $table) {
            $table->id();
            $table->integer('league_id');
            $table->integer('global_play_id');
            $table->enum('status', ['hidden', 'customized']);
            $table->integer('customized_play_id')->nullable();
            $table->integer('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('league_id')->references('id')->on('leagues')->cascadeOnDelete();
            $table->foreign('global_play_id')->references('id')->on('plays')->cascadeOnDelete();
            $table->foreign('customized_play_id')->references('id')->on('plays')->nullOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();

            $table->unique(['league_id', 'global_play_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_play_overrides');
    }
};
