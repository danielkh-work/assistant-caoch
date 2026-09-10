<?php

namespace App\Observers;

use App\Models\Role;
use App\Models\User;

/**
 * Keeps a user's Spatie "user type" role in sync with the plain users.role
 * enum column, so every place role gets set (signup, addQB, AssistantCoachController
 * store/update, and any future call site) automatically gets the matching
 * permission-bearing role without repeating assignRole() calls everywhere.
 *
 * Only ever touches the user-type role slot - subscription-tier roles (assigned
 * separately, e.g. copied from the head coach on creation) are left untouched.
 */
class UserRoleSyncObserver
{
    private const TYPE_ROLES = ['head_coach', 'assistant_coach', 'qb', 'performance_coach'];

    public function created(User $user): void
    {
        $this->sync($user);
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged('role')) {
            $this->sync($user);
        }
    }

    private function sync(User $user): void
    {
        if (! in_array($user->role, self::TYPE_ROLES, true)) {
            return;
        }

        $nonTypeRoles = $user->roles()
            ->where('category', '!=', Role::CATEGORY_USER_TYPE)
            ->pluck('name');

        $user->syncRoles($nonTypeRoles->push($user->role));
    }
}
