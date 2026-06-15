<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websocket_practice_scoreboards', function (Blueprint $table) {
            if (! Schema::hasColumn('websocket_practice_scoreboards', 'h_mark_position')) {
                $column = $table->string('h_mark_position', 32)
                    ->nullable()
                    ->default('hmark_center');

                if (Schema::hasColumn('websocket_practice_scoreboards', 'coverage_category')) {
                    $column->after('coverage_category');
                }
            }
        });

        Schema::table('websocket_scoreboards', function (Blueprint $table) {
            if (! Schema::hasColumn('websocket_scoreboards', 'h_mark_position')) {
                $column = $table->string('h_mark_position', 32)
                    ->nullable()
                    ->default('hmark_center');

                if (Schema::hasColumn('websocket_scoreboards', 'coverage_category')) {
                    $column->after('coverage_category');
                } elseif (Schema::hasColumn('websocket_scoreboards', 'possession')) {
                    $column->after('possession');
                }
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
