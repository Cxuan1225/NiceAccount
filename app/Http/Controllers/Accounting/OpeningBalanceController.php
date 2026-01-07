<?php

namespace App\Http\Controllers\Accounting;

use App\DTO\Accounting\OpeningBalance\OpeningBalanceStoreDTO;
use App\Http\Requests\Accounting\OpeningBalance\OpeningBalanceStoreRequest;
use App\Models\Accounting\ChartOfAccount;
use App\Services\Accounting\OpeningBalance\OpeningBalanceService;
use Inertia\Inertia;
use RuntimeException;

class OpeningBalanceController extends BaseAccountingController {
    public function create(OpeningBalanceService $service) {
        $accounts = ChartOfAccount::query()
            ->where('company_id', $this->companyId)
            ->where('is_active', 1)
            ->orderBy('account_code')
            ->get([ 'id', 'account_code', 'name', 'type' ]);

        $obe = ChartOfAccount::query()
            ->where('company_id', $this->companyId)
            ->systemRole(ChartOfAccount::ROLE_OPENING_BALANCE_EQUITY)
            ->first([ 'id', 'account_code', 'name', 'type' ]);

        if (!$obe) {
            throw new RuntimeException('Opening Balance Equity account is not configured.');
        }


        return Inertia::render('Accountings/OpeningBalance/Create', [
            'accounts'             => $accounts,
            'openingBalanceEquity' => $obe,
        ]);
    }

    public function store(OpeningBalanceStoreRequest $request, OpeningBalanceService $service) {
        $dto = OpeningBalanceStoreDTO::fromRequest($request, $this->companyId);

        $service->create($dto);

        return redirect()->route('coa.index');
    }
}
