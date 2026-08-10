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

        if (Schema::hasColumn('devices', 'telemetry_synced_at') && ! Schema::hasColumn('devices', 'battery_signal_synced_at')) {
            Schema::table('devices', function (Blueprint $table) {
                $table->renameColumn('telemetry_synced_at', 'battery_signal_synced_at');
            });

            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            if (! Schema::hasColumn('devices', 'battery_signal_synced_at')) {
                $table->timestamp('battery_signal_synced_at')->nullable()->after('signal_strength');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('devices') || ! Schema::hasColumn('devices', 'battery_signal_synced_at')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('battery_signal_synced_at');
        });
    }
};
