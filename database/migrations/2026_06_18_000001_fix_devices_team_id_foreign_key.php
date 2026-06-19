<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No-op: devices.team_id is unsignedBigInteger without FK (see create_devices_table).
    }

    public function down(): void
    {
        //
    }
};
