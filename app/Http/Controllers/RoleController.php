<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\DataTables;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:role_permission.manage');
    }

    public function index(Request $request)
    {
        $data = Role::userType()->get();

        if ($request->ajax()) {
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('permissions', function ($row) {
                    return $row->permissions->pluck('name')->implode(', ');
                })
                ->addColumn('action', function ($row) {
                    $editUrl = route('roles.edit', ['id' => $row->id]);
                    $deleteUrl = route('roles.destroy', ['id' => $row->id]);

                    return '
                        <a href="' . $editUrl . '" class="btn btn-warning btn-sm me-1">Edit</a>
                        <form action="' . $deleteUrl . '" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure?\')">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    ';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('roles.index');
    }

    public function create()
    {
        $permissions = Permission::all();

        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
            'category' => Role::CATEGORY_USER_TYPE,
        ]);

        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit($id)
    {
        $role = Role::userType()->findOrFail($id);
        // '*' pinned first - buried alphabetically among ~76 rows it's easy
        // to miss, and it's the one checkbox that controls everything else.
        $permissions = Permission::all()->sortByDesc(fn ($p) => $p->name === '*')->values();

        return view('roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::userType()->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
        ]);

        $role->name = $request->name;
        $role->save();

        $permissions = $request->permissions ?? [];

        // head_coach must always keep a way back into this panel, or a
        // mistake here locks every head coach out with no UI path back in
        // (already happened once - saving with everything unchecked wiped
        // the role entirely). Unlike before, this no longer force-pins '*' -
        // admins can now deliberately move head_coach off the wildcard onto
        // an explicit list. It only guarantees role_permission.manage (or a
        // wildcard that covers it) survives the save.
        if ($role->name === 'head_coach') {
            $keepsAdminAccess = in_array('*', $permissions, true)
                || in_array('role_permission.*', $permissions, true)
                || in_array('role_permission.manage', $permissions, true);

            if (! $keepsAdminAccess) {
                $permissions[] = 'role_permission.manage';
            }
        }

        $role->syncPermissions($permissions);

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy($id)
    {
        $role = Role::userType()->findOrFail($id);
        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }
}
