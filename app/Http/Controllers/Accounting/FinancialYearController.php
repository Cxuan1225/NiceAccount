<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Requests\Accounting\PostingPeriods\FinancialYearStoreRequest;
use App\Models\Accounting\FinancialYear;
use App\Services\Accounting\PostingPeriods\FinancialYearCloseService;
use App\Services\Accounting\PostingPeriods\FinancialYearService;
use Carbon\Carbon;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class FinancialYearController extends BaseAccountingController {
    public function store(FinancialYearStoreRequest $request): Response|InertiaResponse {
        $validated = $request->validated();
        $name = is_string($validated['name'] ?? null) ? $validated['name'] : '';
        $start = $validated['start_date'] ?? null;
        $end = $validated['end_date'] ?? null;

        FinancialYearService::createWithPeriods(
            $this->companyId,
            $name,
            Carbon::parse(is_string($start) ? $start : null),
            Carbon::parse(is_string($end) ? $end : null),
        );

        // ✅ match route name you actually registered
        return redirect()->route('posting_periods.index');
    }

    public function close(FinancialYear $financialYear, FinancialYearCloseService $service): Response|InertiaResponse {
        abort_unless((int) $financialYear->company_id === (int) $this->companyId, 404);

        $service->close($this->companyId, (int) $financialYear->id);

        return redirect()->route('posting_periods.index')->with('success', 'Financial year closed.');
    }
}
