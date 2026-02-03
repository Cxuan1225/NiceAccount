<?php

namespace App\Services\Accounting\Reports;

use App\Support\Accounting\NormalBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class BalanceSheetReportService {
    /**
     * @return array{
     *   filters:array{as_at:string, status:string, status_label:string, show_zero:bool},
     *   assets:array<int, array{id:int, account_code:string, name:string, type:string, parent_id:int|null, debit:float, credit:float, balance:float, is_active:bool, company_id:int}>,
     *   liabilities:array<int, array{id:int, account_code:string, name:string, type:string, parent_id:int|null, debit:float, credit:float, balance:float, is_active:bool, company_id:int}>,
     *   equity:array<int, array{id:int, account_code:string, name:string, type:string, parent_id:int|null, debit:float, credit:float, balance:float, is_active:bool, company_id:int}>,
     *   retainedEarnings:float,
     *   retainedEarningsMapped:bool,
     *   retainedEarningsAccountId:int|null,
     *   totals:array{
     *     totalAssets:float,
     *     totalLiabilities:float,
     *     totalEquityBase:float,
     *     totalEquity:float,
     *     totalLiabEquity:float,
     *     isBalanced:bool,
     *     difference:float
     *   }
     * }
     */
    public function build(
        int $companyId,
        string $asAt,
        string $status,        // normalized '' = ALL, else POSTED/DRAFT/VOID
        string $statusRaw,     // original input for v-model
        string $statusLabel,
        bool $showZero,
    ) : array {
        // 0) Company setting: retained earnings account id (nullable)
        $retainedAccountId = DB::table('companies')
            ->where('id', $companyId)
            ->value('retained_earnings_account_id');

        $retainedAccountId = is_numeric($retainedAccountId) ? (int) $retainedAccountId : null;

        // 1) Pull ALL COA for this company (need parent rollup)
        /** @var Collection<int, \stdClass> $coas */
        $coas = DB::table('chart_of_accounts')
            ->select('id', 'account_code', 'name', 'type', 'parent_id', 'is_active', 'company_id')
            ->where('company_id', $companyId)
            ->get();

        // 2) Aggregate sums per account up to as_at
        /** @var Collection<int, \stdClass> $sums */
        $sums = DB::table('journal_entry_lines as l')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->selectRaw('l.account_id, COALESCE(SUM(l.debit),0) as debit_sum, COALESCE(SUM(l.credit),0) as credit_sum')
            ->where('l.company_id', $companyId)
            ->where('e.company_id', $companyId)
            ->whereDate('e.entry_date', '<=', $asAt)
            ->when($status !== '', fn ($q) => $q->whereRaw('UPPER(e.status) = ?', [ $status ]))
            ->groupBy('l.account_id')
            ->get()
            ->keyBy('account_id');

        $normalBalance = fn (string $type, float $debit, float $credit)
            => NormalBalance::amount($type, $debit, $credit);


        // 3) Build node map (account => computed balance), then roll-up to parents
        /** @var array<int, array{id:int, account_code:string, name:string, type:string, parent_id:int|null, debit:float, credit:float, balance:float, is_active:bool, company_id:int}> $nodes */
        $nodes = [];
        foreach ($coas as $a) {
            $id = $this->toInt($a->id ?? null);
            $row = $sums->get($id);

            $debit  = $row ? $this->toFloat($row->debit_sum ?? null) : 0.0;
            $credit = $row ? $this->toFloat($row->credit_sum ?? null) : 0.0;
            $type = $this->toString($a->type ?? null);
            $parentIdRaw = $a->parent_id ?? null;
            $parentId = is_numeric($parentIdRaw) ? (int) $parentIdRaw : null;

            $nodes[$id] = [
                'id'           => $id,
                'account_code' => $this->toString($a->account_code ?? null),
                'name'         => $this->toString($a->name ?? null),
                'type'         => $type,
                'parent_id'    => $parentId,
                'debit'        => $debit,
                'credit'       => $credit,
                'balance'      => $normalBalance($type, $debit, $credit),
                'is_active'    => (bool) $a->is_active,
                'company_id'   => $this->toInt($a->company_id ?? null),
            ];
        }

        // Roll-up leaf balances into parent nodes
        foreach ($nodes as $id => $n) {
            /** @var array{id:int, account_code:string, name:string, type:string, parent_id:int|null, debit:float, credit:float, balance:float, is_active:bool, company_id:int} $n */
            $bal = (float) $n['balance'];
            $p   = $n['parent_id'];

            while (is_int($p)) {
                if (!array_key_exists($p, $nodes)) {
                    break;
                }
                /** @var array{id:int, account_code:string, name:string, type:string, parent_id:int|null, debit:float, credit:float, balance:float, is_active:bool, company_id:int} $parent */
                $parent = $nodes[$p];
                $parent['balance'] += $bal;
                $nodes[$p] = $parent;
                $p = $parent['parent_id'];
            }
        }

        // 4) Retained Earnings = (Income - Expense) up to asAt (same status filter)
        /** @var \stdClass|null $pl */
        $pl = DB::table('journal_entry_lines as l')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->join('chart_of_accounts as c', 'c.id', '=', 'l.account_id')
            ->selectRaw("
                SUM(CASE WHEN c.type = 'INCOME'  THEN (l.credit - l.debit) ELSE 0 END) as income_total,
                SUM(CASE WHEN c.type = 'EXPENSE' THEN (l.debit - l.credit) ELSE 0 END) as expense_total
            ")
            ->where('l.company_id', $companyId)
            ->where('e.company_id', $companyId)
            ->whereDate('e.entry_date', '<=', $asAt)
            ->when($status !== '', fn ($q) => $q->whereRaw('UPPER(e.status) = ?', [ $status ]))
            ->first();

        $incomeTotal      = $pl ? $this->toFloat($pl->income_total ?? null) : 0.0;
        $expenseTotal     = $pl ? $this->toFloat($pl->expense_total ?? null) : 0.0;
        $retainedEarnings = $incomeTotal - $expenseTotal;

        // 4.1) Validate retained earnings mapping (must be EQUITY + same company)
        $injectRetainedIntoCoa = false;

        if ($retainedAccountId && isset($nodes[$retainedAccountId])) {
            $reNode = $nodes[$retainedAccountId];
            if ($reNode['company_id'] === (int) $companyId && $reNode['type'] === 'EQUITY') {
                $injectRetainedIntoCoa = true;
            }
        }

        // 4.2) Inject retained earnings into selected equity COA (so it appears as normal row and rolls up)
        if ($injectRetainedIntoCoa && $retainedAccountId !== null && isset($nodes[$retainedAccountId])) {
            $delta = (float) $retainedEarnings;

            $nodes[$retainedAccountId]['balance'] += $delta;

            $p = $nodes[$retainedAccountId]['parent_id'];
            while (is_int($p)) {
                if (!array_key_exists($p, $nodes)) {
                    break;
                }
                /** @var array{id:int, account_code:string, name:string, type:string, parent_id:int|null, debit:float, credit:float, balance:float, is_active:bool, company_id:int} $parent */
                $parent = $nodes[$p];
                $parent['balance'] += $delta;
                $nodes[$p] = $parent;
                $p = $parent['parent_id'];
            }
        }

        // 5) Partition BS accounts
        $assets      = [];
        $liabilities = [];
        $equity      = [];

        foreach ($nodes as $n) {
            /** @var array{id:int, account_code:string, name:string, type:string, parent_id:int|null, debit:float, credit:float, balance:float, is_active:bool, company_id:int} $n */
            if (!in_array($n['type'], [ 'ASSET', 'LIABILITY', 'EQUITY' ], true)) {
                continue;
            }

            if (!$showZero && abs((float) $n['balance']) < 0.00001) {
                continue;
            }

            if ($n['type'] === 'ASSET') $assets[] = $n;
            if ($n['type'] === 'LIABILITY') $liabilities[] = $n;
            if ($n['type'] === 'EQUITY') $equity[] = $n;
        }

        // If not mapped, show virtual retained earnings row
        if (!$injectRetainedIntoCoa) {
            if ($showZero || abs((float) $retainedEarnings) >= 0.00001) {
                $equity[] = [
                    'id'           => 0,
                    'account_code' => '',
                    'name'         => 'Retained Earnings (Unmapped)',
                    'type'         => 'EQUITY',
                    'parent_id'    => null,
                    'debit'        => 0.0,
                    'credit'       => 0.0,
                    'balance'      => (float) $retainedEarnings,
                    'is_active'    => true,
                    'company_id'   => (int) $companyId,
                ];
            }
        }

        // 6) Sort
        $sortByCode = function (array $a, array $b): int {
            $ac  = is_string($a['account_code'] ?? null) ? $a['account_code'] : '';
            $bc  = is_string($b['account_code'] ?? null) ? $b['account_code'] : '';
            $cmp = strcmp($ac, $bc);
            if ($cmp !== 0) return $cmp;
            $an = is_string($a['name'] ?? null) ? $a['name'] : '';
            $bn = is_string($b['name'] ?? null) ? $b['name'] : '';
            return strcmp($an, $bn);
        };
        usort($assets, $sortByCode);
        usort($liabilities, $sortByCode);
        usort($equity, $sortByCode);

        // 7) Totals + equation check
        $totalAssets      = array_sum(array_map(fn ($x) => (float) $x['balance'], $assets));
        $totalLiabilities = array_sum(array_map(fn ($x) => (float) $x['balance'], $liabilities));
        $totalEquityBase  = array_sum(array_map(fn ($x) => (float) $x['balance'], $equity));
        $totalEquity      = $totalEquityBase;

        $totalLiabEquity = $totalLiabilities + $totalEquity;
        $isBalanced      = abs($totalAssets - $totalLiabEquity) < 0.01;

        return [
            'filters'                   => [
                'as_at'        => $asAt,
                'status'       => $statusRaw, // keep select v-model
                'status_label' => $statusLabel,
                'show_zero'    => $showZero,
            ],
            'assets'                    => $assets,
            'liabilities'               => $liabilities,
            'equity'                    => $equity,

            'retainedEarnings'          => $retainedEarnings,
            'retainedEarningsMapped'    => $injectRetainedIntoCoa,
            'retainedEarningsAccountId' => $injectRetainedIntoCoa ? $retainedAccountId : null,

            'totals'                    => [
                'totalAssets'      => $totalAssets,
                'totalLiabilities' => $totalLiabilities,
                'totalEquityBase'  => $totalEquityBase,
                'totalEquity'      => $totalEquity,
                'totalLiabEquity'  => $totalLiabEquity,
                'isBalanced'       => $isBalanced,
                'difference'       => $totalAssets - $totalLiabEquity,
            ],
        ];
    }

    private function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function toFloat(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function toString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return is_numeric($value) ? (string) $value : '';
    }
}
