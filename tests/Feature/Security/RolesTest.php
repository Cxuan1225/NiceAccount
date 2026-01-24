<?php

use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $perms = [
        'security.roles.view',
        'security.roles.create',
        'security.roles.update',
        'security.roles.delete',
    ];

    foreach ($perms as $p) {
        Permission::firstOrCreate([ 'name' => $p, 'guard_name' => 'web' ]);
    }

    Role::firstOrCreate([ 'name' => 'Super Admin', 'guard_name' => 'web' ])
        ->syncPermissions(Permission::all());

    Role::firstOrCreate([ 'name' => 'Admin', 'guard_name' => 'web' ])
        ->syncPermissions([ 'security.roles.view' ]);
});

it('blocks roles index without permission', function () {
    $company = makeCompany();
    $user = makeUser($company);

    actingAs($user);

    get('/security/roles')->assertForbidden();
});

it('allows roles index with permission', function () {
    $company = makeCompany();
    $admin = makeUser($company);
    $admin->assignRole('Admin');

    actingAs($admin);

    get('/security/roles')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Security/Roles/Index')
            ->has('roles.data')
        );
});

it('super admin bypasses role permissions', function () {
    $company = makeCompany();
    $super = makeUser($company);
    $super->assignRole('Super Admin');

    Role::findByName('Super Admin')->syncPermissions([]);

    actingAs($super);

    get('/security/roles')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Security/Roles/Index')
        );
});
