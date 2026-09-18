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
                    $names = $row->permissions->pluck('name');

                    if ($names->contains('*')) {
                        return '<span class="perm-badge perm-badge--all"><i class="fas fa-unlock"></i> Full access</span>';
                    }

                    if ($names->isEmpty()) {
                        return '<span class="text-muted small">No permissions</span>';
                    }

                    // A flat comma list of 10+ dotted names is unreadable - group by
                    // entity (same mental model as the role editor) so a row reads
                    // as "which areas, how much" instead of a wall of text.
                    $counts = $names
                        ->groupBy(fn ($name) => str_contains($name, '.') ? explode('.', $name)[0] : 'legacy')
                        ->map->count()
                        ->sortKeys();

                    return $counts->map(function ($count, $entity) {
                        $label = e(ucwords(str_replace('_', ' ', $entity)));
                        return "<span class=\"perm-badge\">{$label} <b>{$count}</b></span>";
                    })->implode(' ');
                })
                ->addColumn('action', function ($row) {
                    $editUrl = route('roles.edit', ['id' => $row->id]);
                    $deleteUrl = route('roles.destroy', ['id' => $row->id]);

                    return '
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="' . $editUrl . '" class="btn btn-outline-primary" title="Edit"><i class="fas fa-pen"></i> Edit</a>
                            <button type="button" class="btn btn-outline-danger role-delete-btn" title="Delete"
                                data-form="delete-form-' . $row->id . '" data-name="' . e($row->name) . '">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                        <form id="delete-form-' . $row->id . '" action="' . $deleteUrl . '" method="POST" class="d-none">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                        </form>
                    ';
                })
                ->rawColumns(['permissions', 'action'])
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
