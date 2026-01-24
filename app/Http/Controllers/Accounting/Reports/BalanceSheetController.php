<?php

namespace App\Http\Controllers\Accounting\Reports;

use App\Http\Controllers\Accounting\BaseAccountingController;
use App\Http\Requests\Accounting\Reports\BalanceSheetReportRequest;
use App\DTO\Accounting\Reports\ReportFiltersDTO;
use App\Support\Accounting\Reports\ReportFiltersFactory as Filters;
use App\Services\Accounting\Reports\BalanceSheetReportService;
use Inertia\Inertia;

class BalanceSheetController extends BaseAccountingController {
    public function index(BalanceSheetReportRequest $request, BalanceSheetReportService $service) {
        $companyId = $this->companyId;

        $base = ReportFiltersDTO::fromRequest($request, $companyId);
        $asAt = Filters::asAt($request); // as_at / asAtDb

        $result = $service->build(
            companyId: $companyId,
            asAt: $asAt['asAtDb'],
            status: $base->status,
            statusRaw: $base->statusRaw,
            statusLabel: $base->statusLabel,
            showZero: $base->showZero,
        );

        // ✅ standardized snake_case filters for UI
        $result['filters'] = array_merge($base->toFilterArray(), [
            'as_at' => $asAt['as_at'],
        ]);

        return Inertia::render('Accountings/AccountingReports/BalanceSheet/Index', $result);
    }
}
