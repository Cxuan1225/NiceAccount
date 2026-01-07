<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\AuditTrailController;

Route::get(
    '/',
    fn() => Auth::check()
    ? redirect()->route('dashboard')
    : (Features::enabled(Features::registration())
        ? redirect()->route('register')
        : redirect()->route('login')
    )
)->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/sales/invoices', fn() => Inertia::render('Sales/Invoices/Index'))->name('sales.invoices.index');
    Route::get('/sales/payments', fn() => Inertia::render('Sales/Payments/Index'))->name('sales.payments.index');
    Route::get('/expenses/bills', fn() => Inertia::render('Expenses/Bills/Index'))->name('expenses.bills.index');
    Route::resource('customers', CustomerController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::get('/reports', fn() => Inertia::render('Reports/Index'))->name('reports.index');
    Route::get('/settings', fn() => Inertia::render('Settings/Index'))->name('settings.index');
});


Route::get('/audit-trails', [AuditTrailController::class, 'index'])
    ->name('audit-trails.index');

require __DIR__ . '/accounting.php';
require __DIR__ . '/settings.php';
