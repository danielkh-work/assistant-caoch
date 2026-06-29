<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('team_group_play')) {
            Schema::create('team_group_play', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('team_group_id');
                $table->integer('play_id');
                $table->timestamps();

                $table->unique(['team_group_id', 'play_id']);
                $table->index('team_group_id');
                $table->index('play_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('team_group_play');
    }
};
