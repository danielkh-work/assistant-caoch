<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websocket_scoreboards', function (Blueprint $table) {
            if (! Schema::hasColumn('websocket_scoreboards', 'regulation_ended_at')) {
                $table->timestamp('regulation_ended_at')->nullable()->after('sys_time');
            }
            if (! Schema::hasColumn('websocket_scoreboards', 'last_meaningful_activity_at')) {
                $table->timestamp('last_meaningful_activity_at')->nullable()->after('regulation_ended_at');
            }
        });

        Schema::table('websocket_practice_scoreboards', function (Blueprint $table) {
            if (! Schema::hasColumn('websocket_practice_scoreboards', 'regulation_ended_at')) {
                $table->timestamp('regulation_ended_at')->nullable()->after('sys_time');
            }
            if (! Schema::hasColumn('websocket_practice_scoreboards', 'last_meaningful_activity_at')) {
                $table->timestamp('last_meaningful_activity_at')->nullable()->after('regulation_ended_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('websocket_scoreboards', function (Blueprint $table) {
            if (Schema::hasColumn('websocket_scoreboards', 'last_meaningful_activity_at')) {
                $table->dropColumn('last_meaningful_activity_at');
            }
            if (Schema::hasColumn('websocket_scoreboards', 'regulation_ended_at')) {
                $table->dropColumn('regulation_ended_at');
            }
        });

        Schema::table('websocket_practice_scoreboards', function (Blueprint $table) {
            if (Schema::hasColumn('websocket_practice_scoreboards', 'last_meaningful_activity_at')) {
                $table->dropColumn('last_meaningful_activity_at');
            }
            if (Schema::hasColumn('websocket_practice_scoreboards', 'regulation_ended_at')) {
                $table->dropColumn('regulation_ended_at');
            }
        });
    }
};
