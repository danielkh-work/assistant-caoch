<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_denied_permissions', function (Blueprint $table) {
            $table->id();
            // users.id and permissions.id are plain signed INT in this app (not
            // BIGINT UNSIGNED), so foreignId()/unsignedInteger() would both mismatch.
            $table->integer('user_id');
            $table->integer('permission_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_denied_permissions');
    }
};
