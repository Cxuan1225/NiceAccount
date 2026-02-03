<?php

namespace App\Services\Accounting\ChartOfAccounts;

use App\DTO\Accounting\ChartOfAccounts\CoaIndexFiltersDTO;
use App\DTO\Accounting\ChartOfAccounts\CoaUpsertDTO;
use App\Models\Accounting\ChartOfAccount;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ChartOfAccountService {
    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function list(CoaIndexFiltersDTO $dto) : LengthAwarePaginator {
        return ChartOfAccount::query()
            ->where('company_id', $dto->companyId)
            ->when($dto->q !== '', function ($builder) use ($dto) {
                $builder->where(function ($sub) use ($dto) {
                    $sub->where('account_code', 'like', "%{$dto->q}%")
                        ->orWhere('name', 'like', "%{$dto->q}%");
                });
            })
            ->when($dto->type, fn ($b) => $b->where('type', $dto->type))
            ->orderBy('account_code')
            ->paginate($dto->perPage)
            ->withQueryString()
            ->through(function ($a) {
                /** @var array<string, mixed> $row */
                $row = [
                    'id'           => (int) $a->id,
                    'account_code' => (string) $a->account_code,
                    'name'         => (string) $a->name,
                    'type'         => (string) $a->type,
                    'parent_id'    => $a->parent_id ? (int) $a->parent_id : null,
                    'is_active'    => (bool) $a->is_active,
                ];

                return $row;
            });
    }

    /**
     * @return Collection<int, array{id:int, account_code:string, name:string}>
     */
    public function parentsOptions(int $companyId, ?int $excludeId = null): Collection {
        return ChartOfAccount::query()
            ->where('company_id', $companyId)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->orderBy('account_code')
            ->get([ 'id', 'account_code', 'name' ])
            ->map(fn ($p) => [
                'id'           => (int) $p->id,
                'account_code' => (string) $p->account_code,
                'name'         => (string) $p->name,
            ]);
    }

    public function create(CoaUpsertDTO $dto) : ChartOfAccount {
        return ChartOfAccount::create([
            'company_id'   => $dto->companyId,
            'account_code' => $dto->accountCode,
            'name'         => $dto->name,
            'type'         => $dto->type,
            'parent_id'    => $dto->parentId,
            'is_active'    => $dto->isActive,
        ]);
    }

    public function update(ChartOfAccount $account, CoaUpsertDTO $dto) : ChartOfAccount {
        $account->update([
            'account_code' => $dto->accountCode,
            'name'         => $dto->name,
            'type'         => $dto->type,
            'parent_id'    => $dto->parentId,
            'is_active'    => $dto->isActive,
        ]);

        return $account;
    }

    public function delete(ChartOfAccount $account) : void {
        // next level: prevent delete if used by journal lines
        $account->delete();
    }
}
