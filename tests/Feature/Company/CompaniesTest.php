<?php

use App\Models\Company;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $perms = [
        'company.companies.view',
        'company.companies.create',
        'company.companies.update',
        'company.companies.delete',
    ];

    foreach ($perms as $p) {
        Permission::firstOrCreate([ 'name' => $p, 'guard_name' => 'web' ]);
    }

    Role::firstOrCreate([ 'name' => 'Super Admin', 'guard_name' => 'web' ])
        ->syncPermissions(Permission::all());

    Role::firstOrCreate([ 'name' => 'Admin', 'guard_name' => 'web' ])
        ->syncPermissions([ 'company.companies.view', 'company.companies.update' ]);
});

it('blocks companies index without permission', function () {
    $company = makeCompany();
    $user = makeUser($company);

    actingAs($user);

    get('/companies')->assertForbidden();
});

it('allows companies index with permission', function () {
    $company = makeCompany();
    $admin = makeUser($company);
    $admin->assignRole('Admin');

    actingAs($admin);

    get('/companies')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Company/Companies/Index')
            ->has('companies.data')
        );
});

it('scopes companies index to user company for non-super-admin', function () {
    $companyA = makeCompany([ 'name' => 'Alpha' ]);
    $companyB = makeCompany([ 'name' => 'Beta' ]);

    $adminA = makeUser($companyA);
    $adminA->assignRole('Admin');

    actingAs($adminA);

    get('/companies')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Company/Companies/Index')
            ->has('companies.data', fn ($data) => collect($data)
                ->every(fn ($row) => (int) $row['id'] === (int) $companyA->id)
            )
        );
});

it('forbids editing another company for non-super-admin', function () {
    $companyA = makeCompany();
    $companyB = makeCompany();

    $adminA = makeUser($companyA);
    $adminA->assignRole('Admin');

    actingAs($adminA);

    get("/companies/{$companyB->id}/edit")->assertForbidden();
});

it('super admin can view all companies', function () {
    $companyA = makeCompany([ 'name' => 'Alpha' ]);
    $companyB = makeCompany([ 'name' => 'Beta' ]);

    $super = makeUser($companyA);
    $super->assignRole('Super Admin');

    actingAs($super);

    get('/companies')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Company/Companies/Index')
            ->has('companies.data', fn ($data) => collect($data)->pluck('id')->sort()->values()->all()
                === collect([ $companyA->id, $companyB->id ])->sort()->values()->all()
            )
        );
});
