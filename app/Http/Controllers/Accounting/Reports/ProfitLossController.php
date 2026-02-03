<?php

namespace App\Http\Controllers\Accounting\Reports;

use App\Http\Controllers\Accounting\BaseAccountingController;
use App\Http\Requests\Accounting\Reports\ProfitLossReportRequest;
use App\DTO\Accounting\Reports\ReportFiltersDTO;
use App\Services\Accounting\Reports\ProfitLossReportService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class ProfitLossController extends BaseAccountingController {
    public function index(ProfitLossReportRequest $request, ProfitLossReportService $service): Response|InertiaResponse {
        $companyId = $this->companyId;

        $filters = ReportFiltersDTO::fromRequest($request, $companyId);

        $result = $service->build($filters);

        return Inertia::render('Accountings/AccountingReports/ProfitLoss/Index', $result);
    }
}
