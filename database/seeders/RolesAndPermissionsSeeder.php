<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       // Define permissions
       $permissions = [
        'add_upload_play',
        'view_upload_play',
        'edit_upload_play',
        'list_upload_play'
    
    ];

    // Create Permissions
    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    // Define roles
    $roles = [
        'Beginner' => [
            'add_upload_play',
            'view_upload_play',
            'edit_upload_play',
            'list_upload_play'
        ],
        'Expert' => [
            'add_upload_play',
            'view_upload_play',
            'edit_upload_play',
            'list_upload_play'
        ],
        'Pro' => [
            'add_upload_play',
            'view_upload_play',
            'edit_upload_play',
            'list_upload_play'
        ]
    ];

    // Create roles and assign permissions
        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }
    }
}
