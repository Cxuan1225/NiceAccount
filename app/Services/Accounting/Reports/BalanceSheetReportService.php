<?php

namespace App\Services\Accounting\Reports;

use App\Support\Accounting\NormalBalance;
use Illuminate\Support\Facades\DB;

class BalanceSheetReportService {
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

        $retainedAccountId = $retainedAccountId ? (int) $retainedAccountId : null;

        // 1) Pull ALL COA for this company (need parent rollup)
        $coas = DB::table('chart_of_accounts')
            ->select('id', 'account_code', 'name', 'type', 'parent_id', 'is_active', 'company_id')
            ->where('company_id', $companyId)
            ->get();

        // 2) Aggregate sums per account up to as_at
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
        $nodes = [];
        foreach ($coas as $a) {
            $row = $sums->get($a->id);

            $debit  = $row ? (float) $row->debit_sum : 0.0;
            $credit = $row ? (float) $row->credit_sum : 0.0;

            $nodes[(int) $a->id] = [
                'id'           => (int) $a->id,
                'account_code' => (string) $a->account_code,
                'name'         => (string) $a->name,
                'type'         => (string) $a->type,
                'parent_id'    => $a->parent_id ? (int) $a->parent_id : null,
                'debit'        => $debit,
                'credit'       => $credit,
                'balance'      => $normalBalance((string) $a->type, $debit, $credit),
                'is_active'    => (bool) $a->is_active,
                'company_id'   => (int) $a->company_id,
            ];
        }

        // Roll-up leaf balances into parent nodes
        foreach ($nodes as $id => $n) {
            $bal = (float) $n['balance'];
            $p   = $n['parent_id'];

            while ($p && isset($nodes[$p])) {
                $nodes[$p]['balance'] += $bal;
                $p                     = $nodes[$p]['parent_id'];
            }
        }

        // 4) Retained Earnings = (Income - Expense) up to asAt (same status filter)
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

        $incomeTotal      = $pl ? (float) $pl->income_total : 0.0;
        $expenseTotal     = $pl ? (float) $pl->expense_total : 0.0;
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
        if ($injectRetainedIntoCoa) {
            $delta = (float) $retainedEarnings;

            $nodes[$retainedAccountId]['balance'] += $delta;

            $p = $nodes[$retainedAccountId]['parent_id'];
            while ($p && isset($nodes[$p])) {
                $nodes[$p]['balance'] += $delta;
                $p                     = $nodes[$p]['parent_id'];
            }
        }

        // 5) Partition BS accounts
        $assets      = [];
        $liabilities = [];
        $equity      = [];

        foreach ($nodes as $n) {
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
        $sortByCode = function ($a, $b) {
            $ac  = (string) ($a['account_code'] ?? '');
            $bc  = (string) ($b['account_code'] ?? '');
            $cmp = strcmp($ac, $bc);
            if ($cmp !== 0) return $cmp;
            return strcmp((string) $a['name'], (string) $b['name']);
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
}
