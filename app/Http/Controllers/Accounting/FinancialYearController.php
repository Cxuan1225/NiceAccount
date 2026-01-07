<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Requests\Accounting\PostingPeriods\FinancialYearStoreRequest;
use App\Models\Accounting\FinancialYear;
use App\Services\Accounting\PostingPeriods\FinancialYearCloseService;
use App\Services\Accounting\PostingPeriods\FinancialYearService;
use Carbon\Carbon;

class FinancialYearController extends BaseAccountingController {
    public function store(FinancialYearStoreRequest $request) {
        $validated = $request->validated();

        FinancialYearService::createWithPeriods(
            $this->companyId,
            (string) $validated['name'],
            Carbon::parse($validated['start_date']),
            Carbon::parse($validated['end_date']),
        );

        // ✅ match route name you actually registered
        return redirect()->route('posting_periods.index');
    }

    public function close(FinancialYear $financialYear, FinancialYearCloseService $service) {
        abort_unless((int) $financialYear->company_id === (int) $this->companyId, 404);

        $service->close($this->companyId, (int) $financialYear->id);

        return redirect()->route('posting_periods.index')->with('success', 'Financial year closed.');
    }
}
