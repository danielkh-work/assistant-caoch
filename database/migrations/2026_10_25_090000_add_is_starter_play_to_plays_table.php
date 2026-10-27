<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Default true: every existing Global Play stays visible to brand-new
        // leagues exactly as it is today until an admin opts specific plays out.
        Schema::table('plays', function (Blueprint $table) {
            $table->boolean('is_starter_play')->default(true)->after('is_global');
        });
    }

    public function down(): void
    {
        Schema::table('plays', function (Blueprint $table) {
            $table->dropColumn('is_starter_play');
        });
    }
};
