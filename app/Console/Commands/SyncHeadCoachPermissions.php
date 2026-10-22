<?php

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Backfill for an environment whose head_coach role still holds only '*'.
 *
 * UserTypeRolesSeeder already grants every permission by name, but seeding an
 * environment that is already live rewrites the other roles too, and those may
 * have been adjusted by hand in the admin panel. This touches head_coach and
 * nothing else.
 */
class SyncHeadCoachPermissions extends Command
{
    protected $signature = 'permissions:sync-head-coach
                            {--dry-run : Show what would change without writing}';

    protected $description = 'Give the head_coach role every permission by name (keeps the other roles untouched)';

    public function handle(): int
    {
        $role = Role::where('name', 'head_coach')->first();

        if (! $role) {
            $this->error('There is no head_coach role in this database.');

            return self::FAILURE;
        }

        $all = Permission::pluck('name');
        $have = $role->permissions->pluck('name');
        $missing = $all->diff($have);

        $this->line("head_coach currently holds {$have->count()} of {$all->count()} permissions.");

        if ($missing->isEmpty()) {
            $this->info('Nothing to do.');

            return self::SUCCESS;
        }

        $this->line("Would add {$missing->count()}: " . $missing->take(8)->implode(', ') . ($missing->count() > 8 ? ' ...' : ''));

        if ($this->option('dry-run')) {
            $this->warn('[DRY RUN] Nothing written.');

            return self::SUCCESS;
        }

        $role->syncPermissions($all->all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("Done - head_coach now holds {$role->fresh()->permissions->count()} permissions.");

        return self::SUCCESS;
    }
}
