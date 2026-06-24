<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('team_groups')) {
            return;
        }

        Schema::create('team_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('league_id')->nullable();
            $table->string('group_name', 255);
            $table->text('description')->nullable();
            $table->string('type', 50);
            $table->json('players')->nullable();
            $table->json('practice_players')->nullable();
            $table->unsignedTinyInteger('group_level')->comment('Segment size: 7, 11, or 12');
            $table->string('status', 32)->default('active');
            $table->timestamps();

            $table->foreign('team_id')->references('id')->on('league_teams')->onDelete('cascade');
            $table->foreign('league_id')->references('id')->on('leagues')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_groups');
    }
};
