<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mobile_sessions')) {
            Schema::create('mobile_sessions', function (Blueprint $table) {
                $table->id();
                $table->uuid('session_id')->unique();
                // users.id is int(11) in this schema — avoid bigint FK mismatch.
                $table->unsignedInteger('mobile_user_id')->nullable()->index();
                $table->enum('status', ['pending', 'approved', 'expired'])->default('pending');
                $table->timestamps();
            });

            return;
        }

        Schema::table('mobile_sessions', function (Blueprint $table) {
            $table->unsignedInteger('mobile_user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('mobile_sessions')) {
            return;
        }

        Schema::table('mobile_sessions', function (Blueprint $table) {
            $table->unsignedInteger('mobile_user_id')->nullable(false)->change();
        });
    }
};
