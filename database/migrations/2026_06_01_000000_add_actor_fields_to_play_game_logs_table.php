<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('play_game_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('actor_id')->nullable()->after('id');
            $table->string('actor_role')->nullable()->after('actor_id');
            $table->string('actor_name', 255)->nullable()->after('actor_role');
            $table->json('players_out')->nullable();
            $table->json('players_in')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('play_game_logs', function (Blueprint $table) {
            $table->dropColumn(['actor_id', 'actor_role', 'actor_name', 'players_out', 'players_in']);
        });
    }
};
