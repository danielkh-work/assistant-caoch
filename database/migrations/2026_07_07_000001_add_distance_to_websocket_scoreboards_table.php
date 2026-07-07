<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websocket_practice_scoreboards', function (Blueprint $table) {
            if (! Schema::hasColumn('websocket_practice_scoreboards', 'distance')) {
                $table->unsignedTinyInteger('distance')->nullable()->after('down');
            }
        });

        Schema::table('websocket_scoreboards', function (Blueprint $table) {
            if (! Schema::hasColumn('websocket_scoreboards', 'distance')) {
                $table->unsignedTinyInteger('distance')->nullable()->after('down');
            }
        });
    }

    public function down(): void
    {
        Schema::table('websocket_practice_scoreboards', function (Blueprint $table) {
            if (Schema::hasColumn('websocket_practice_scoreboards', 'distance')) {
                $table->dropColumn('distance');
            }
        });

        Schema::table('websocket_scoreboards', function (Blueprint $table) {
            if (Schema::hasColumn('websocket_scoreboards', 'distance')) {
                $table->dropColumn('distance');
            }
        });
    }
};
