<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Accounting\ChartOfAccountController;
use App\Http\Controllers\Accounting\OpeningBalanceController;
use App\Http\Controllers\Accounting\JournalEntryController;
use App\Http\Controllers\Accounting\LedgerController;
use App\Http\Controllers\Accounting\PostingPeriodController;
use App\Http\Controllers\Accounting\FinancialYearController;

use App\Http\Controllers\Accounting\Reports\TrialBalanceController;
use App\Http\Controllers\Accounting\Reports\ProfitLossController;
use App\Http\Controllers\Accounting\Reports\BalanceSheetController;
use App\Http\Controllers\Accounting\Reports\GeneralLedgerController;

Route::middleware(['auth', 'verified'])
    ->prefix('accountings')
    ->group(function () {

        // Chart of Accounts
        Route::get('/chart-of-accounts', [ChartOfAccountController::class, 'index'])->name('coa.index');
        Route::get('/chart-of-accounts/create', [ChartOfAccountController::class, 'create'])->name('coa.create');
        Route::post('/chart-of-accounts', [ChartOfAccountController::class, 'store'])->name('coa.store');
        Route::get('/chart-of-accounts/{account}/edit', [ChartOfAccountController::class, 'edit'])->name('coa.edit');
        Route::put('/chart-of-accounts/{account}', [ChartOfAccountController::class, 'update'])->name('coa.update');
        Route::delete('/chart-of-accounts/{account}', [ChartOfAccountController::class, 'destroy'])->name('coa.destroy');

        // Opening Balance
        Route::get('/opening-balance', [OpeningBalanceController::class, 'create'])->name('opening-balance.create');
        Route::post('/opening-balance', [OpeningBalanceController::class, 'store'])->name('opening-balance.store');

        // Journal Entries
        Route::get('/journal-entries', [JournalEntryController::class, 'index'])->name('je.index');
        Route::get('/journal-entries/create', [JournalEntryController::class, 'create'])->name('je.create');
        Route::post('/journal-entries', [JournalEntryController::class, 'store'])->name('je.store');

        Route::get('/journal-entries/{journalEntry}', [JournalEntryController::class, 'show'])
            ->name('accountings.journal-entries.show');

        Route::post('/journal-entries/{journalEntry}/reverse', [JournalEntryController::class, 'reverse'])
            ->name('accountings.journal-entries.reverse');

        // Ledger
        Route::get('/ledger/{account}', [LedgerController::class, 'show'])
            ->name('accountings.ledger.show');

        // Posting Periods + Financial Years
        Route::get('/posting-periods', [PostingPeriodController::class, 'index'])
            ->name('accountings.posting-periods.index');

        Route::post('/posting-periods/{period}/lock', [PostingPeriodController::class, 'lock'])
            ->name('accountings.posting-periods.lock');

        Route::post('/posting-periods/{period}/unlock', [PostingPeriodController::class, 'unlock'])
            ->name('accountings.posting-periods.unlock');

        Route::post('/posting-periods/bulk-lock', [PostingPeriodController::class, 'bulkLock'])
            ->name('accountings.posting-periods.bulk-lock');

        Route::post('/posting-periods/bulk-unlock', [PostingPeriodController::class, 'bulkUnlock'])
            ->name('accountings.posting-periods.bulk-unlock');

        Route::post('/financial-years', [FinancialYearController::class, 'store'])
            ->name('accountings.financial-years.store');

        Route::post('/financial-years/{financialYear}/close', [FinancialYearController::class, 'close'])
            ->name('accountings.financial-years.close');

        // Accounting Reports
        Route::prefix('accounting-reports')
            ->name('accountings.accounting-reports.')
            ->group(function () {

            Route::get('/', fn() => redirect()->route('accountings.accounting-reports.trial-balance.index'))
                ->name('index');

            // Trial Balance
            Route::get('/trial-balance', [TrialBalanceController::class, 'index'])
                ->name('trial-balance.index');

            Route::get('/trial-balance/export/pdf', [TrialBalanceController::class, 'exportPdf'])
                ->name('trial-balance.export.pdf');

            Route::get('/trial-balance/export/excel', [TrialBalanceController::class, 'exportExcel'])
                ->name('trial-balance.export.excel');

            // Profit & Loss
            Route::get('/profit-loss', [ProfitLossController::class, 'index'])
                ->name('profit-loss.index');

            // Balance Sheet
            Route::get('/balance-sheet', [BalanceSheetController::class, 'index'])
                ->name('balance-sheet.index');

            // General Ledger
            Route::get('/general-ledger', [GeneralLedgerController::class, 'index'])
                ->name('general-ledger.index');
        });
    });
