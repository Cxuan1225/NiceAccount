<?php

namespace App\Services\Accounting\Reports;

use App\DTO\Accounting\Reports\ReportFiltersDTO;
use Illuminate\Support\Facades\DB;

class ProfitLossReportService {
    public function build(ReportFiltersDTO $f) : array {
        $rows = DB::table('journal_entry_lines as l')
            ->join('journal_entries as je', 'je.id', '=', 'l.journal_entry_id')
            ->join('chart_of_accounts as coa', 'coa.id', '=', 'l.account_id')
            ->where('je.company_id', $f->companyId)
            ->where('l.company_id', $f->companyId)
            ->where('coa.company_id', $f->companyId)
            ->whereIn('coa.type', [ 'INCOME', 'EXPENSE' ])
            ->when($f->status !== '', function ($q) use ($f) {
                // If your DB already stores UPPER status, you can do:
                // $q->where('je.status', $f->status);
                $q->whereRaw('UPPER(je.status) = ?', [ $f->status ]);
            })
            ->when($f->from, fn ($q) => $q->whereDate('je.entry_date', '>=', $f->from))
            ->when($f->to, fn ($q) => $q->whereDate('je.entry_date', '<=', $f->to))
            ->groupBy('coa.id', 'coa.account_code', 'coa.name', 'coa.type')
            ->orderBy('coa.account_code')
            ->selectRaw("
                coa.id as account_id,
                coa.account_code as account_code,
                coa.name as name,
                coa.type as type,
                ROUND(
                    SUM(
                        CASE
                            WHEN coa.type = 'INCOME'  THEN (l.credit - l.debit)
                            WHEN coa.type = 'EXPENSE' THEN (l.debit - l.credit)
                            ELSE 0
                        END
                    ),
                2) as amount
            ")
            ->get()
            ->map(function ($r) {
                return [
                    'account_id'   => (int) $r->account_id,
                    'account_code' => (string) $r->account_code,
                    'name'         => (string) $r->name,
                    'type'         => (string) $r->type,
                    'amount'       => (float) $r->amount,
                ];
            });

        if (!$f->showZero) {
            $rows = $rows->filter(fn ($r) => abs((float) $r['amount']) > 0.000001)->values();
        }

        $incomeRows  = $rows->where('type', 'INCOME')->values();
        $expenseRows = $rows->where('type', 'EXPENSE')->values();

        $totalIncome  = round($incomeRows->sum('amount'), 2);
        $totalExpense = round($expenseRows->sum('amount'), 2);
        $netProfit    = round($totalIncome - $totalExpense, 2);

        return [
            'filters'  => $f->toFilterArray(),
            'sections' => [
                'income'   => $incomeRows,
                'expenses' => $expenseRows,
            ],
            'totals'   => [
                'income'     => $totalIncome,
                'expenses'   => $totalExpense,
                'net_profit' => $netProfit,
            ],
        ];
    }
}
