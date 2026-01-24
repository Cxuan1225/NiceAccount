<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\Company\CompanyCreateController;
use App\Http\Controllers\Company\CompanySettingsController;
use App\Http\Controllers\Company\CompanySwitchController;


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
    Route::get('/companies', [ CompanySwitchController::class, 'index' ])->name('companies.index');
    Route::post('/companies/switch', [ CompanySwitchController::class, 'store' ])->name('companies.switch');
    Route::get('/companies/create', [ CompanyCreateController::class, 'create' ])->name('companies.create');
    Route::post('/companies', [ CompanyCreateController::class, 'store' ])->name('companies.store');
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

Route::get('/audit-trails', [ AuditTrailController::class, 'index' ])
    ->middleware([ 'auth', 'verified', 'active_company' ])
    ->name('audit-trails.index');


require __DIR__ . '/accounting.php';
require __DIR__ . '/settings.php';
