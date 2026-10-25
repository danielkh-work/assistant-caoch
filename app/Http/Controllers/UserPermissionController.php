<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class UserPermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:role_permission.manage');
    }

    public function edit(User $user)
    {
        $allPermissions = Permission::all();

        $roleGrantedNames = $user->getPermissionsViaRoles()->pluck('name');
        $directNames = $user->getDirectPermissions()->pluck('name');
        $deniedNames = $user->deniedPermissions()->pluck('name');


        // Per-permission state for the tri-state selector: 'denied' always wins
        // display-wise, then 'direct' (granted beyond role), then 'inherited'
        // (role gives it, no override), then 'none' (nothing grants it at all).
        $rows = $allPermissions->map(function ($permission) use ($roleGrantedNames, $directNames, $deniedNames) {
            $state = 'none';
            if ($deniedNames->contains($permission->name)) {
                $state = 'denied';
            } elseif ($directNames->contains($permission->name)) {
                $state = 'direct';
            } elseif ($roleGrantedNames->contains($permission->name)) {
                $state = 'inherited';
            }

            return (object) ['permission' => $permission, 'state' => $state];
        });
        return view('users.permissions', compact('user', 'rows'));
    }

    public function update(Request $request, User $user)
    {
        $submitted = $request->input('permission_state', []); // ['1' => 'inherited'|'direct'|'denied', ...]

        $directIds = [];
        $deniedIds = [];

        foreach ($submitted as $permissionId => $state) {
            if ($state === 'direct') {
                $directIds[] = (int) $permissionId;
            } elseif ($state === 'denied') {
                $deniedIds[] = (int) $permissionId;
            }
        }

        $user->syncPermissions(Permission::whereIn('id', $directIds)->get());

        // Only keep a denial for a permission the user would otherwise hold.
        //
        // The form has no "none" radio - a permission nothing grants arrives
        // here as 'denied', so saving this screen without touching it used to
        // write a deny row for every permission the user simply did not have.
        // Those rows are not harmless: a denial beats both the role and the
        // '*' wildcard (User::hasPermissionTo checks it first), so granting the
        // permission later silently did nothing, which is exactly the "we
        // allowed it in admin and it still does not work" report.
        $user->load('roles.permissions');
        $held = $user->getPermissionsViaRoles()->pluck('name')
            ->merge(Permission::whereIn('id', $directIds)->pluck('name'));

        $effectiveDeniedIds = Permission::whereIn('id', $deniedIds)
            ->get()
            ->filter(fn ($permission) => $held->contains($permission->name))
            ->pluck('id')
            ->all();

        $user->deniedPermissions()->sync($effectiveDeniedIds);

        return redirect()->route('users.index')->with('success', "Permissions updated for {$user->name}.");
    }
}
