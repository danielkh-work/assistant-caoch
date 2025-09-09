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
        Schema::table('users', function (Blueprint $table) {

            $table->enum('role', ['head_coach', 'assistant_coach'])->default('head_coach')->after('email');
            $table->unsignedBigInteger('head_coach_id')->nullable()->after('role');
            $table->foreign('head_coach_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['head_coach_id']);
            $table->dropColumn(['role', 'head_coach_id']);
        });
    }
};
