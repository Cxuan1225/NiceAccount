<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\Company\CompanyController;
use App\Http\Controllers\Company\CompanySettingsController;
use App\Http\Controllers\Company\CompanySwitchController;
use App\Http\Controllers\Security\PermissionController;
use App\Http\Controllers\Security\RoleController;
use App\Http\Controllers\Security\UserController;


Route::get(
    '/',
    fn () => Auth::check()
    ? redirect()->route('dashboard')
    : (Features::enabled(Features::registration())
        ? redirect()->route('register')
        : redirect()->route('login')
    )
)->name('home');

Route::middleware([ 'auth', 'verified' ])->group(function () {
    Route::get('/companies/select', [ CompanySwitchController::class, 'index' ])->name('companies.select');
    Route::post('/companies/switch', [ CompanySwitchController::class, 'store' ])->name('companies.switch');

    Route::get('/companies', [ CompanyController::class, 'index' ])
        ->middleware('can:company.companies.view')
        ->name('companies.index');
    Route::get('/companies/create', [ CompanyController::class, 'create' ])
        ->middleware('can:company.companies.create')
        ->name('companies.create');
    Route::post('/companies', [ CompanyController::class, 'store' ])
        ->middleware('can:company.companies.create')
        ->name('companies.store');
    Route::get('/companies/{company}/edit', [ CompanyController::class, 'edit' ])
        ->middleware('can:company.companies.update')
        ->name('companies.edit');
    Route::put('/companies/{company}', [ CompanyController::class, 'update' ])
        ->middleware('can:company.companies.update')
        ->name('companies.update');
    Route::delete('/companies/{company}', [ CompanyController::class, 'destroy' ])
        ->middleware('can:company.companies.delete')
        ->name('companies.destroy');
});

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware([ 'auth', 'verified', 'active_company' ])->name('dashboard');

Route::middleware([ 'auth', 'verified', 'active_company' ])->group(function () {
    Route::get('/company/settings', [ CompanySettingsController::class, 'edit' ])->name('company.settings.edit');
    Route::put('/company/settings', [ CompanySettingsController::class, 'update' ])->name('company.settings.update');

    Route::get('/sales/invoices', fn () => Inertia::render('Sales/Invoices/Index'))->name('sales.invoices.index');
    Route::get('/sales/payments', fn () => Inertia::render('Sales/Payments/Index'))->name('sales.payments.index');
    Route::get('/expenses/bills', fn () => Inertia::render('Expenses/Bills/Index'))->name('expenses.bills.index');

    Route::resource('customers', CustomerController::class)
        ->only([ 'index', 'create', 'store', 'edit', 'update', 'destroy' ]);

    Route::get('/reports', fn () => Inertia::render('Reports/Index'))->name('reports.index');
    Route::get('/settings', fn () => Inertia::render('Settings/Index'))->name('settings.index');
});

Route::middleware([ 'auth', 'verified' ])
    ->prefix('security')
    ->name('security.')
    ->group(function () {
        Route::get('/users', [ UserController::class, 'index' ])
            ->middleware('can:security.users.view')
            ->name('users.index');
        Route::get('/users/create', [ UserController::class, 'create' ])
            ->middleware('can:security.users.create')
            ->name('users.create');
        Route::post('/users', [ UserController::class, 'store' ])
            ->middleware('can:security.users.create')
            ->name('users.store');
        Route::get('/users/{user}/edit', [ UserController::class, 'edit' ])
            ->middleware('can:security.users.update')
            ->name('users.edit');
        Route::put('/users/{user}', [ UserController::class, 'update' ])
            ->middleware('can:security.users.update')
            ->name('users.update');
        Route::delete('/users/{user}', [ UserController::class, 'destroy' ])
            ->middleware('can:security.users.delete')
            ->name('users.destroy');

        Route::get('/roles', [ RoleController::class, 'index' ])
            ->middleware('can:security.roles.view')
            ->name('roles.index');
        Route::get('/roles/create', [ RoleController::class, 'create' ])
            ->middleware('can:security.roles.create')
            ->name('roles.create');
        Route::post('/roles', [ RoleController::class, 'store' ])
            ->middleware('can:security.roles.create')
            ->name('roles.store');
        Route::get('/roles/{role}/edit', [ RoleController::class, 'edit' ])
            ->middleware('can:security.roles.update')
            ->name('roles.edit');
        Route::put('/roles/{role}', [ RoleController::class, 'update' ])
            ->middleware('can:security.roles.update')
            ->name('roles.update');
        Route::delete('/roles/{role}', [ RoleController::class, 'destroy' ])
            ->middleware('can:security.roles.delete')
            ->name('roles.destroy');

        Route::get('/permissions', [ PermissionController::class, 'index' ])
            ->middleware('can:security.permissions.view')
            ->name('permissions.index');
    });

Route::get('/audit-trails', [ AuditTrailController::class, 'index' ])
    ->middleware([ 'auth', 'verified', 'active_company' ])
    ->name('audit-trails.index');


require __DIR__ . '/accounting.php';
require __DIR__ . '/settings.php';
