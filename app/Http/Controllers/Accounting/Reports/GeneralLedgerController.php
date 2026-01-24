<?php

namespace App\Http\Controllers\Accounting\Reports;

use App\Http\Controllers\Accounting\BaseAccountingController;
use App\Http\Requests\Accounting\Reports\GeneralLedgerReportRequest;
use App\DTO\Accounting\Reports\ReportFiltersDTO;
use App\Support\Accounting\Reports\ReportFiltersFactory as Filters;
use App\Services\Accounting\Reports\GeneralLedgerReportService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GeneralLedgerController extends BaseAccountingController {
    public function index(GeneralLedgerReportRequest $request, GeneralLedgerReportService $service) {
        $companyId = $this->companyId;

        $accounts = DB::table('chart_of_accounts')
            ->select('id', 'account_code', 'name', 'type')
            ->where('company_id', $companyId)
            ->orderBy('account_code')
            ->get()
            ->map(fn ($a) => [
                'id'           => (int) $a->id,
                'account_code' => (string) $a->account_code,
                'name'         => (string) $a->name,
                'type'         => (string) $a->type,
                'label'        => trim($a->account_code . ' - ' . $a->name),
            ]);

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
