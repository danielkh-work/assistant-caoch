<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'category')) {
                $table->string('category')->default('user_type')->after('name');
            }
        });

        // Existing subscription-tier roles must be tagged explicitly - the default
        // above is for the NEW user-type roles (head_coach/assistant_coach/qb/
        // performance_coach), not these pre-existing ones.
        DB::table('roles')->whereIn('name', [
            'Classic basic',
            'Classic advance',
            'HD HUMAN DASHBOARD basic',
            'Pro basic',
            'Pro advance',
        ])->update(['category' => 'feature_tier']);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
