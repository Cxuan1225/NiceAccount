<?php

namespace App\Services\Accounting\Reports;

use App\DTO\Accounting\Reports\ReportFiltersDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ProfitLossReportService {
    /**
     * @return array{
     *   filters:array<string, mixed>,
     *   sections:array{
     *     income:Collection<int, array{account_id:int, account_code:string, name:string, type:string, amount:float}>,
     *     expenses:Collection<int, array{account_id:int, account_code:string, name:string, type:string, amount:float}>
     *   },
     *   totals:array{income:float, expenses:float, net_profit:float}
     * }
     */
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
            ->map(function ($r): array {
                return [
                    'account_id'   => $this->toInt($r->account_id ?? null),
                    'account_code' => $this->toString($r->account_code ?? null),
                    'name'         => $this->toString($r->name ?? null),
                    'type'         => $this->toString($r->type ?? null),
                    'amount'       => $this->toFloat($r->amount ?? null),
                ];
            });

        if (!$f->showZero) {
            $rows = $rows->filter(fn ($r) => abs((float) $r['amount']) > 0.000001)->values();
        }

        $incomeRows  = $rows->where('type', 'INCOME')->values();
        $expenseRows = $rows->where('type', 'EXPENSE')->values();

        $totalIncome  = round($this->toFloat($incomeRows->sum('amount')), 2);
        $totalExpense = round($this->toFloat($expenseRows->sum('amount')), 2);
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
