<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websocket_scoreboards', function (Blueprint $table) {
            $table->unsignedBigInteger('session_id')->nullable()->after('game_id');
        });
    }

    public function down(): void
    {
        Schema::table('websocket_scoreboards', function (Blueprint $table) {
            $table->dropColumn('session_id');
        });
    }
};
