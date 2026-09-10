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
        $user->deniedPermissions()->sync($deniedIds);

        return redirect()->route('users.index')->with('success', "Permissions updated for {$user->name}.");
    }
}
