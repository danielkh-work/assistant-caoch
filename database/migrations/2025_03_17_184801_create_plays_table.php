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
        Schema::create('plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->onDelete('cascade');
            $table->string('play_name');
            $table->unsignedBigInteger('play_type');
            $table->unsignedBigInteger('zone_selection');
            $table->string('min_expected_yard');
            $table->string('max_expected_yard');
            $table->integer('pre_snap_motion');
            $table->integer('play_action_fake');
            $table->json('preferred_downs')->nullable(); // e.g. ["1st", "2nd"]
            $table->string('video_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plays');
    }
};
