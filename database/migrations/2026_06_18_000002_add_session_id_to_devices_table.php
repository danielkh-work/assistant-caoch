<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('devices')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            if (! Schema::hasColumn('devices', 'session_id')) {
                $table->string('session_id')->nullable()->after('paired_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('devices') || ! Schema::hasColumn('devices', 'session_id')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('session_id');
        });
    }
};
