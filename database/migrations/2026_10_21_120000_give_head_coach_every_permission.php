<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Backfill for the head_coach role, which until now held only '*'.
 *
 * UserTypeRolesSeeder has been changed to grant every permission by name,
 * but a seeder only helps an environment somebody remembers to seed - this
 * one was last run on staging in September, which is how the assistant coach
 * ended up holding permission names the routes no longer use. Deploys run
 * migrations, so the backfill goes here too.
 *
 * Only head_coach is touched. The other roles are left exactly as they are,
 * because their permission sets may have been adjusted by hand in the admin
 * panel and this is not the place to overwrite that.
 */
return new class extends Migration
{
    public function up(): void
    {
        $role = Role::where('name', 'head_coach')->first();

        if (! $role) {
            return;
        }

        $role->syncPermissions(Permission::pluck('name')->all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $role = Role::where('name', 'head_coach')->first();

        if (! $role || ! Permission::where('name', '*')->exists()) {
            return;
        }

        $role->syncPermissions(['*']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
