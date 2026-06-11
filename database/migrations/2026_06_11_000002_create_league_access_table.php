<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Grants additional head coaches access to a league.
     * Ownership remains on leagues.user_id; rows here are for shared access only.
     */
    public function up(): void
    {
        if (Schema::hasTable('league_access')) {
            Schema::drop('league_access');
        }

        $leagueIdType = $this->referenceIdColumnType('leagues');
        $userIdType = $this->referenceIdColumnType('users');

        DB::statement("
            CREATE TABLE `league_access` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `league_id` {$leagueIdType} NOT NULL,
                `user_id` {$userIdType} NOT NULL,
                `access_type` varchar(255) NOT NULL DEFAULT 'shared',
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `league_access_league_id_user_id_unique` (`league_id`, `user_id`),
                CONSTRAINT `league_access_league_id_foreign`
                    FOREIGN KEY (`league_id`) REFERENCES `leagues` (`id`) ON DELETE CASCADE,
                CONSTRAINT `league_access_user_id_foreign`
                    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('league_access');
    }

    private function referenceIdColumnType(string $table): string
    {
        $row = DB::selectOne(
            'SELECT COLUMN_TYPE AS column_type
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?',
            [$table, 'id']
        );

        return $row->column_type ?? 'bigint unsigned';
    }
};
