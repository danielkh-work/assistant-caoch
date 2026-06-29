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
        Schema::table('play_game_modes', function (Blueprint $table) {
            if (! Schema::hasColumn('play_game_modes', 'device_id')) {
                $table->unsignedBigInteger('device_id')->nullable()->after('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('play_game_modes', function (Blueprint $table) {
            if (Schema::hasColumn('play_game_modes', 'device_id')) {
                $table->dropColumn('device_id');
            }
        });
    }
};
