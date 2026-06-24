<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('team_groups', 'name')) {
            DB::statement("ALTER TABLE `team_groups` MODIFY `name` VARCHAR(255) NOT NULL DEFAULT ''");
        }
    }

    public function down(): void {}
};
