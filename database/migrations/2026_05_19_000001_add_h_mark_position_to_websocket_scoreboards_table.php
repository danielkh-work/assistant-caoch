<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websocket_practice_scoreboards', function (Blueprint $table) {
            if (!Schema::hasColumn('websocket_practice_scoreboards', 'h_mark_position')) {
                $table->string('h_mark_position', 32)
                    ->nullable()
                    ->default('hmark_center')
                    ->after('coverage_category');
            }
        });

        Schema::table('websocket_scoreboards', function (Blueprint $table) {
            if (!Schema::hasColumn('websocket_scoreboards', 'h_mark_position')) {
                $table->string('h_mark_position', 32)
                    ->nullable()
                    ->default('hmark_center')
                    ->after('coverage_category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('websocket_practice_scoreboards', function (Blueprint $table) {
            $table->dropColumn('h_mark_position');
        });

        Schema::table('websocket_scoreboards', function (Blueprint $table) {
            $table->dropColumn('h_mark_position');
        });
    }
};
