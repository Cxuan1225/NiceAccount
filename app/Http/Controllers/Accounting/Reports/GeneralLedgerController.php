<?php

namespace App\Http\Controllers\Accounting\Reports;

use App\Http\Controllers\Accounting\BaseAccountingController;
use App\Http\Requests\Accounting\Reports\GeneralLedgerReportRequest;
use App\DTO\Accounting\Reports\ReportFiltersDTO;
use App\Support\Accounting\Reports\ReportFiltersFactory as Filters;
use App\Services\Accounting\Reports\GeneralLedgerReportService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class GeneralLedgerController extends BaseAccountingController {
    public function index(GeneralLedgerReportRequest $request, GeneralLedgerReportService $service): Response|InertiaResponse {
        $companyId = $this->companyId;

        $accounts = DB::table('chart_of_accounts')
            ->select('id', 'account_code', 'name', 'type')
            ->where('company_id', $companyId)
            ->orderBy('account_code')
            ->get()
            ->map(function ($a) {
                $id = is_numeric($a->id ?? null) ? (int) $a->id : 0;
                $accountCode = is_string($a->account_code ?? null) ? $a->account_code : '';
                $name = is_string($a->name ?? null) ? $a->name : '';
                $type = is_string($a->type ?? null) ? $a->type : '';

                return [
                    'id'           => $id,
                    'account_code' => $accountCode,
                    'name'         => $name,
                    'type'         => $type,
                    'label'        => trim($accountCode . ' - ' . $name),
                ];
            });

        $base    = ReportFiltersDTO::fromRequest($request, $companyId);
        $account = Filters::accountId($request); // account_id / accountIdDb

        $result = $service->build(
            companyId: $companyId,
            accountId: $account['accountIdDb'],
            from: $base->from,
            to: $base->to,
            status: $base->status,
            statusRaw: $base->statusRaw,
            statusLabel: $base->statusLabel,
            showZero: $base->showZero,
        );

        // ✅ standardized snake_case filters for UI
        $result['filters'] = array_merge($base->toFilterArray(), [
            'account_id' => $account['account_id'], // UI-safe string
        ]);

        // optional: keep selectedAccount mapping
        if (isset($result['account'])) {
            $result['selectedAccount'] = $result['account'];
            unset($result['account']);
        }

        return Inertia::render('Accountings/AccountingReports/GeneralLedger/Index', array_merge($result, [
            'accounts' => $accounts,
        ]));
    }
}
