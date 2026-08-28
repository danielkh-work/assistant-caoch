<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websocket_practice_scoreboards', function (Blueprint $table) {
            if (! Schema::hasColumn('websocket_practice_scoreboards', 'first_down_reference_position')) {
                $table->integer('first_down_reference_position')->nullable()->after('distance');
            }
        });

        Schema::table('websocket_scoreboards', function (Blueprint $table) {
            if (! Schema::hasColumn('websocket_scoreboards', 'first_down_reference_position')) {
                $table->integer('first_down_reference_position')->nullable()->after('distance');
            }
        });
    }

    public function down(): void
    {
        Schema::table('websocket_practice_scoreboards', function (Blueprint $table) {
            if (Schema::hasColumn('websocket_practice_scoreboards', 'first_down_reference_position')) {
                $table->dropColumn('first_down_reference_position');
            }
        });

        Schema::table('websocket_scoreboards', function (Blueprint $table) {
            if (Schema::hasColumn('websocket_scoreboards', 'first_down_reference_position')) {
                $table->dropColumn('first_down_reference_position');
            }
        });
    }
};
