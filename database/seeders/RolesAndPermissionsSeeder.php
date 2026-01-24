<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'access dashboard',
            'view companies',
            'manage companies',
            'view reports',
            'manage settings',
            'manage chart of accounts',
            'manage customers',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);
        $admin = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);
        $staff = Role::firstOrCreate([
            'name' => 'Staff',
            'guard_name' => 'web',
        ]);

        $superAdmin->syncPermissions($permissions);
        $admin->syncPermissions([
            'access dashboard',
            'view companies',
            'manage companies',
            'view reports',
            'manage chart of accounts',
            'manage customers',
        ]);
        $staff->syncPermissions([
            'access dashboard',
            'view companies',
        ]);
    }
}
