<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plays', function (Blueprint $table) {
            // Drop the old JSON column
            $table->dropColumn('preferred_downs');

            // Add new integer column
            $table->integer('preferred_down')->nullable();

            // Add new possession field
            $table->enum('possession', ['offensive', 'defensive'])->default('offensive');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plays', function (Blueprint $table) {
            // Rollback changes
            $table->dropColumn('preferred_down');
            $table->dropColumn('possession');
            $table->json('preferred_downs')->nullable(); // Restore old field
        });
    }
};
