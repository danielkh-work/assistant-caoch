<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websocket_practice_scoreboards', function (Blueprint $table) {
            $table->integer('timer_remaining')->nullable()->after('time');
            $table->string('sys_time')->nullable()->after('timer_remaining');
            $table->string('coverage_category')->nullable()->after('possession');
        });
    }

    public function down(): void
    {
        Schema::table('websocket_practice_scoreboards', function (Blueprint $table) {
            $table->dropColumn(['timer_remaining', 'sys_time', 'coverage_category']);
        });
    }
};
