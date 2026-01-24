<?php

namespace App\Http\Controllers\Accounting\Reports;

use App\Http\Controllers\Accounting\BaseAccountingController;
use App\Http\Requests\Accounting\Reports\TrialBalanceReportRequest;
use App\DTO\Accounting\Reports\ReportFiltersDTO;
use App\Services\Accounting\Reports\TrialBalanceReportService;

use App\Exports\TrialBalanceExport;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class TrialBalanceController extends BaseAccountingController {
    public function index(TrialBalanceReportRequest $request, TrialBalanceReportService $service) {
        $companyId = $this->companyId;

        $filters = ReportFiltersDTO::fromRequest($request, $companyId);

        $result = $service->build($filters);

        return Inertia::render('Accountings/AccountingReports/TrialBalance/Index', $result);
    }

    public function exportExcel(TrialBalanceReportRequest $request, TrialBalanceReportService $service) {
        $companyId = $this->companyId;

        $filters = ReportFiltersDTO::fromRequest($request, $companyId);

        $result = $service->build($filters);

        $filename = 'trial-balance_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new TrialBalanceExport($result['rows'], $result['filters'], $result['totals']),
            $filename,
        );
    }

    public function exportPdf(TrialBalanceReportRequest $request, TrialBalanceReportService $service) {
        $companyId = $this->companyId;

        $filters = ReportFiltersDTO::fromRequest($request, $companyId);

        $result = $service->build($filters);

        $pdf = Pdf::loadView('pdf.trial_balance', [
            'filters'     => $result['filters'],
            'rows'        => $result['rows'],
            'totals'      => $result['totals'],
            'companyName' => 'NiceAccount',
        ])->setPaper('a4', 'portrait');

        $filename = 'trial-balance_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }
}
