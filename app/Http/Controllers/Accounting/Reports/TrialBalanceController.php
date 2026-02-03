<?php

namespace App\Http\Controllers\Accounting\Reports;

use App\Http\Controllers\Accounting\BaseAccountingController;
use App\Http\Requests\Accounting\Reports\TrialBalanceReportRequest;
use App\DTO\Accounting\Reports\ReportFiltersDTO;
use App\Services\Accounting\Reports\TrialBalanceReportService;

use App\Exports\TrialBalanceExport;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class TrialBalanceController extends BaseAccountingController {
    public function index(TrialBalanceReportRequest $request, TrialBalanceReportService $service): Response|InertiaResponse {
        $companyId = $this->companyId;

        $filters = ReportFiltersDTO::fromRequest($request, $companyId);

        $result = $service->build($filters);

        return Inertia::render('Accountings/AccountingReports/TrialBalance/Index', $result);
    }

    public function exportExcel(TrialBalanceReportRequest $request, TrialBalanceReportService $service): Response|InertiaResponse {
        $companyId = $this->companyId;

        $filters = ReportFiltersDTO::fromRequest($request, $companyId);

        $result = $service->build($filters);

        $filename = 'trial-balance_' . now()->format('Ymd_His') . '.xlsx';

        /** @var \Illuminate\Support\Collection<int, array<string, mixed>> $rows */
        $rows = $result['rows']->map(function (array $row): array {
            return $row;
        });

        return Excel::download(
            new TrialBalanceExport($rows),
            $filename,
        );
    }

    public function exportPdf(TrialBalanceReportRequest $request, TrialBalanceReportService $service): Response|InertiaResponse {
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
