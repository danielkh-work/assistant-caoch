<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websocket_practice_scoreboards', function (Blueprint $table) {
            if (! Schema::hasColumn('websocket_practice_scoreboards', 'sync_time')) {
                $table->integer('sync_time')->nullable()->after('coverage_category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('websocket_practice_scoreboards', function (Blueprint $table) {
            $table->dropColumn('sync_time');
        });
    }
};
