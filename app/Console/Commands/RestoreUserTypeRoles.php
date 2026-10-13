<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Puts back the user-type role on accounts that lost it.
 *
 * Choosing a subscription used to call syncRoles([$tier]), which drops every
 * other role the user holds - including head_coach, the one carrying '*'. Those
 * accounts end up with a tier and nothing else, and league.create goes with it.
 * The controller no longer does that; this repairs the rows it already touched.
 */
class RestoreUserTypeRoles extends Command
{
    private const TYPE_ROLES = ['head_coach', 'assistant_coach', 'qb', 'performance_coach'];

    protected $signature = 'roles:restore-user-type
                            {--dry-run : List what would change without writing}
                            {--user= : Repair one user id only}';

    protected $description = 'Re-attach the user-type role to accounts whose users.role column has one but whose role relation does not';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $users = User::query()
            ->whereIn('role', self::TYPE_ROLES)
            ->when($this->option('user'), fn ($q, $id) => $q->where('id', $id))
            ->with('roles')
            ->get();

        $broken = $users->filter(function (User $user) {
            return ! $user->roles
                ->where('category', Role::CATEGORY_USER_TYPE)
                ->contains('name', $user->role);
        });

        if ($broken->isEmpty()) {
            $this->info('Nothing to repair - every account already holds its user-type role.');

            return self::SUCCESS;
        }

        $this->warn($broken->count().' of '.$users->count().' accounts are missing their user-type role.');

        foreach ($broken as $user) {
            $held = $user->roles->pluck('name')->implode(', ') ?: '(none)';
            $this->line(sprintf('  #%-5d %-34s has: %-24s needs: %s', $user->id, $user->email, $held, $user->role));

            if ($dry) {
                continue;
            }

            // assignRole, never syncRoles - the tier has to survive this.
            $user->assignRole($user->role);
        }

        if ($dry) {
            $this->comment('Dry run - nothing was written. Drop --dry-run to apply.');

            return self::SUCCESS;
        }

        $this->info('Repaired '.$broken->count().' account(s).');

        return self::SUCCESS;
    }
}
