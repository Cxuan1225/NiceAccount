<?php

use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\put;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $perms = [
        'security.users.view',
        'security.users.create',
        'security.users.update',
        'security.users.delete',
    ];

    foreach ($perms as $p) {
        Permission::firstOrCreate([ 'name' => $p, 'guard_name' => 'web' ]);
    }

    Role::firstOrCreate([ 'name' => 'Super Admin', 'guard_name' => 'web' ])
        ->syncPermissions(Permission::all());

    Role::firstOrCreate([ 'name' => 'Admin', 'guard_name' => 'web' ])
        ->syncPermissions([ 'security.users.view', 'security.users.update' ]);

    Role::firstOrCreate([ 'name' => 'Staff', 'guard_name' => 'web' ]);
});

it('blocks users index without permission', function () {
    $company = makeCompany();
    $user = makeUser($company);

    actingAs($user);

    get('/security/users')->assertForbidden();
});

it('allows users index with permission', function () {
    $company = makeCompany();
    $admin = makeUser($company);
    $admin->assignRole('Admin');

    actingAs($admin);

    get('/security/users')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Security/Users/Index')
            ->has('users.data')
        );
});

it('scopes users index by company for non-super-admin', function () {
    $companyA = makeCompany();
    $companyB = makeCompany();

    $adminA = makeUser($companyA);
    $adminA->assignRole('Admin');

    $userA = makeUser($companyA);
    $userB = makeUser($companyB);

    actingAs($adminA);

    get('/security/users')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Security/Users/Index')
            ->has('users.data', fn ($data) => collect($data)
                ->every(fn ($row) => (int) $row['company_id'] === (int) $companyA->id)
            )
        );
});

it('forbids editing another company user for non-super-admin', function () {
    $companyA = makeCompany();
    $companyB = makeCompany();

    $adminA = makeUser($companyA);
    $adminA->assignRole('Admin');

    $targetB = makeUser($companyB);

    actingAs($adminA);

    put("/security/users/{$targetB->id}", [
        'name' => 'Hacked',
        'email' => $targetB->email,
    ])->assertForbidden();
});

it('super admin can update cross-company user', function () {
    $companyA = makeCompany();
    $companyB = makeCompany();

    $super = makeUser($companyA);
    $super->assignRole('Super Admin');

    $targetB = makeUser($companyB);

    actingAs($super);

    put("/security/users/{$targetB->id}", [
        'name' => 'Updated by super',
        'email' => $targetB->email,
        'company_id' => $companyB->id,
        'roles' => [],
    ])->assertStatus(302);
});
