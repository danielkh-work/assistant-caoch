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
        Schema::table('persional_grouping', function (Blueprint $table) {
             $table->string('status')->nullable()->comment('NULL , substituted ');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('persional_grouping', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
