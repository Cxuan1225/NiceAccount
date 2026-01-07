<?php

namespace App\Http\Controllers\Accounting\Reports;

use App\Http\Controllers\Accounting\BaseAccountingController;
use App\Http\Requests\Accounting\Reports\ProfitLossReportRequest;
use App\DTO\Accounting\Reports\ReportFiltersDTO;
use App\Services\Accounting\Reports\ProfitLossReportService;
use Inertia\Inertia;

class ProfitLossController extends BaseAccountingController {
    public function index(ProfitLossReportRequest $request, ProfitLossReportService $service) {
        $companyId = (int) ($request->user()->company_id ?? 1);

        $filters = ReportFiltersDTO::fromRequest($request, $companyId);

        $result = $service->build($filters);

        return Inertia::render('Accountings/AccountingReports/ProfitLoss/Index', $result);
    }
}
