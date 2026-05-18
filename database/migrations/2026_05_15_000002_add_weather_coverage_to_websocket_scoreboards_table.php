<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websocket_scoreboards', function (Blueprint $table) {
            if (!Schema::hasColumn('websocket_scoreboards', 'weather')) {
                $table->string('weather')->nullable()->after('possession');
            }
            if (!Schema::hasColumn('websocket_scoreboards', 'coverage_category')) {
                $table->string('coverage_category')->nullable()->after('weather');
            }
        });
    }

    public function down(): void
    {
        Schema::table('websocket_scoreboards', function (Blueprint $table) {
            $table->dropColumn(['weather', 'coverage_category']);
        });
    }
};
