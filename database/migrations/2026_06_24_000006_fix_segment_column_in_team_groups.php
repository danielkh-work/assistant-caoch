<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('team_groups', 'segment')) {
            DB::statement("ALTER TABLE `team_groups` ALTER COLUMN `segment` SET DEFAULT 11");
        }
    }

    public function down(): void {}
};
