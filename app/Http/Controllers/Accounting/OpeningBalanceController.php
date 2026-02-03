<?php

namespace App\Http\Controllers\Accounting;

use App\DTO\Accounting\OpeningBalance\OpeningBalanceStoreDTO;
use App\Http\Requests\Accounting\OpeningBalance\OpeningBalanceStoreRequest;
use App\Models\Accounting\ChartOfAccount;
use App\Services\Accounting\OpeningBalance\OpeningBalanceService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;
use RuntimeException;

class OpeningBalanceController extends BaseAccountingController {
    public function create(OpeningBalanceService $service): Response|InertiaResponse {
        $accounts = ChartOfAccount::query()
            ->where('company_id', $this->companyId)
            ->where('is_active', 1)
            ->orderBy('account_code')
            ->get([ 'id', 'account_code', 'name', 'type' ]);

        $obe = ChartOfAccount::query()
            ->where('company_id', $this->companyId)
            ->where(function ($query) {
                $query
                    ->systemRole(ChartOfAccount::ROLE_OPENING_BALANCE_EQUITY)
                    ->orWhereIn('account_code', [ '3200', '3000' ]);
            })
            ->orderByRaw("CASE WHEN system_role = ? THEN 0 WHEN account_code = '3200' THEN 1 ELSE 2 END", [
                ChartOfAccount::ROLE_OPENING_BALANCE_EQUITY,
            ])
            ->first([ 'id', 'account_code', 'name', 'type' ]);

        if (!$obe) {
            return redirect()
                ->route('coa.index')
                ->with('error', 'Opening Balance Equity account is not configured.');
        }


        return Inertia::render('Accountings/OpeningBalance/Create', [
            'accounts'             => $accounts,
            'openingBalanceEquity' => $obe,
        ]);
    }

    public function store(OpeningBalanceStoreRequest $request, OpeningBalanceService $service): Response|InertiaResponse {
        $dto = OpeningBalanceStoreDTO::fromRequest($request, $this->companyId);

        $service->create($dto);

        return redirect()->route('coa.index');
    }
}
