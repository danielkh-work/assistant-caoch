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
               $table->string('weather_status')
                  ->nullable()
                  ->after('type_of_log'); // change column if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('play_game_logs', function (Blueprint $table) {
             $table->dropColumn('weather_status');
        });
    }
};
