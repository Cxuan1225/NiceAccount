<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // example permissions (adjust for NiceAccount)
        $permissions = [
            'view reports',
            'manage settings',
            'manage chart of accounts',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $staff = Role::firstOrCreate(['name' => 'staff']);

        $admin->syncPermissions($permissions);
        $manager->syncPermissions(['view reports']);
        $staff->syncPermissions([]);
    }
}
