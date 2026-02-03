<?php

namespace App\Http\Controllers\Accounting;

use App\DTO\Accounting\ChartOfAccounts\CoaIndexFiltersDTO;
use App\DTO\Accounting\ChartOfAccounts\CoaUpsertDTO;
use App\Http\Requests\Accounting\ChartOfAccounts\CoaIndexRequest;
use App\Http\Requests\Accounting\ChartOfAccounts\CoaStoreRequest;
use App\Http\Requests\Accounting\ChartOfAccounts\CoaUpdateRequest;
use App\Models\Accounting\ChartOfAccount;
use App\Services\Accounting\ChartOfAccounts\ChartOfAccountService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class ChartOfAccountController extends BaseAccountingController {
    public function index(CoaIndexRequest $request, ChartOfAccountService $service): Response|InertiaResponse {
        $filters = CoaIndexFiltersDTO::fromRequest($request, $this->companyId);

        $accounts = $service->list($filters);

        return Inertia::render('Accountings/ChartOfAccounts/Index', [
            'accounts' => $accounts,
            'filters'  => $filters->toFiltersArray(),
            'types'    => $this->coaTypes(),
        ]);
    }

    public function create(ChartOfAccountService $service): Response|InertiaResponse {
        return Inertia::render('Accountings/ChartOfAccounts/Create', [
            'types'   => $this->coaTypes(),
            'parents' => $service->parentsOptions($this->companyId),
        ]);
    }

    public function store(CoaStoreRequest $request, ChartOfAccountService $service): Response|InertiaResponse {
        $dto = CoaUpsertDTO::fromRequest($request, $this->companyId);

        $service->create($dto);

        return redirect()->route('coa.index');
    }

    public function edit(ChartOfAccount $account, ChartOfAccountService $service): Response|InertiaResponse {
        abort_unless($account->company_id == $this->companyId, 404);

        return Inertia::render('Accountings/ChartOfAccounts/Edit', [
            'account' => [
                'id'           => (int) $account->id,
                'account_code' => (string) $account->account_code,
                'name'         => (string) $account->name,
                'type'         => (string) $account->type,
                'parent_id'    => $account->parent_id ? (int) $account->parent_id : null,
                'is_active'    => (bool) $account->is_active,
            ],
            'types'   => $this->coaTypes(),
            'parents' => $service->parentsOptions($this->companyId, (int) $account->id),
        ]);
    }

    public function update(CoaUpdateRequest $request, ChartOfAccount $account, ChartOfAccountService $service): Response|InertiaResponse {
        abort_unless($account->company_id == $this->companyId, 404);

        $dto = CoaUpsertDTO::fromRequest($request, $this->companyId);

        $service->update($account, $dto);

        return redirect()->route('coa.index');
    }

    public function destroy(ChartOfAccount $account, ChartOfAccountService $service): Response|InertiaResponse {
        abort_unless($account->company_id == $this->companyId, 404);

        $service->delete($account);

        return redirect()->route('coa.index');
    }
}
