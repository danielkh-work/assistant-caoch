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
            if (! Schema::hasColumn('devices', 'battery')) {
                $table->integer('battery')->nullable()->after('session_id');
            }
            if (! Schema::hasColumn('devices', 'signal_strength')) {
                $table->integer('signal_strength')->nullable()->after('battery');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('devices')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            if (Schema::hasColumn('devices', 'battery')) {
                $table->dropColumn('battery');
            }
            if (Schema::hasColumn('devices', 'signal_strength')) {
                $table->dropColumn('signal_strength');
            }
        });
    }
};
