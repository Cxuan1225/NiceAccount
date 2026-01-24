<?php

namespace Database\Seeders\Security;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            [
                'name' => 'company.companies.view',
                'label' => 'View Companies',
                'category' => 'Company',
                'description' => 'View companies list and details.',
                'sort_order' => 10,
            ],
            [
                'name' => 'company.companies.create',
                'label' => 'Create Companies',
                'category' => 'Company',
                'description' => 'Create new companies.',
                'sort_order' => 20,
            ],
            [
                'name' => 'company.companies.update',
                'label' => 'Update Companies',
                'category' => 'Company',
                'description' => 'Edit existing companies.',
                'sort_order' => 30,
            ],
            [
                'name' => 'company.companies.delete',
                'label' => 'Delete Companies',
                'category' => 'Company',
                'description' => 'Remove companies.',
                'sort_order' => 40,
            ],
            [
                'name' => 'security.users.view',
                'label' => 'View Users',
                'category' => 'Security',
                'description' => 'View users list and details.',
                'sort_order' => 50,
            ],
            [
                'name' => 'security.users.create',
                'label' => 'Create Users',
                'category' => 'Security',
                'description' => 'Create new users.',
                'sort_order' => 60,
            ],
            [
                'name' => 'security.users.update',
                'label' => 'Update Users',
                'category' => 'Security',
                'description' => 'Edit existing users.',
                'sort_order' => 70,
            ],
            [
                'name' => 'security.users.delete',
                'label' => 'Delete Users',
                'category' => 'Security',
                'description' => 'Remove users.',
                'sort_order' => 80,
            ],
            [
                'name' => 'security.roles.view',
                'label' => 'View Roles',
                'category' => 'Security',
                'description' => 'View roles list and details.',
                'sort_order' => 90,
            ],
            [
                'name' => 'security.roles.create',
                'label' => 'Create Roles',
                'category' => 'Security',
                'description' => 'Create new roles.',
                'sort_order' => 100,
            ],
            [
                'name' => 'security.roles.update',
                'label' => 'Update Roles',
                'category' => 'Security',
                'description' => 'Edit existing roles.',
                'sort_order' => 110,
            ],
            [
                'name' => 'security.roles.delete',
                'label' => 'Delete Roles',
                'category' => 'Security',
                'description' => 'Remove roles.',
                'sort_order' => 120,
            ],
            [
                'name' => 'security.permissions.view',
                'label' => 'View Permissions',
                'category' => 'Security',
                'description' => 'View permissions list and metadata.',
                'sort_order' => 130,
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                [
                    'name' => $permission['name'],
                    'guard_name' => 'web',
                ],
                [
                    'label' => $permission['label'],
                    'category' => $permission['category'],
                    'description' => $permission['description'],
                    'sort_order' => $permission['sort_order'],
                    'is_active' => true,
                ],
            );
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

        $allPermissions = Permission::query()->pluck('name')->all();

        $superAdmin->syncPermissions($allPermissions);
        $admin->syncPermissions([
            'company.companies.view',
            'company.companies.create',
            'company.companies.update',
            'company.companies.delete',
            'security.users.view',
            'security.users.create',
            'security.users.update',
            'security.users.delete',
            'security.roles.view',
            'security.roles.create',
            'security.roles.update',
            'security.roles.delete',
            'security.permissions.view',
        ]);
        $staff->syncPermissions([]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
