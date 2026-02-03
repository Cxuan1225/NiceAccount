<?php

namespace App\Services\Accounting\Reports;

use App\DTO\Accounting\Reports\ReportFiltersDTO;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class TrialBalanceReportService {
    /**
     * @return array{
     *   filters:array<string, mixed>,
     *   rows:Collection<int, array{
     *     account_id:int,
     *     account_code:string,
     *     name:string,
     *     type:string,
     *     is_active:bool,
     *     ending_debit:float,
     *     ending_credit:float
     *   }>,
     *   totals:array{ending_debit:float, ending_credit:float}
     * }
     */
    public function build(ReportFiltersDTO $f) : array {
        // 1) Aggregate per account with COA joined (1 query)
        $agg = DB::table('chart_of_accounts as coa')
            ->leftJoin('journal_entry_lines as l', function (JoinClause $join) use ($f) {
                $join->on('l.account_id', '=', 'coa.id')
                    ->where('l.company_id', '=', $f->companyId);
            })
            ->leftJoin('journal_entries as je', function (JoinClause $join) use ($f) {
                $join->on('je.id', '=', 'l.journal_entry_id')
                    ->where('je.company_id', '=', $f->companyId);
            })
            ->where('coa.company_id', $f->companyId)
            ->selectRaw("
                coa.id as account_id,
                coa.account_code as account_code,
                coa.name as name,
                coa.type as type,
                coa.is_active as is_active,

                -- net = debit - credit (using COALESCE so null lines become 0)
                COALESCE(SUM(
                    CASE
                        WHEN je.id IS NULL THEN 0
                        WHEN (? = '' OR UPPER(je.status) = ?) THEN
                            CASE
                        WHEN (?::date IS NOT NULL AND DATE(je.entry_date) < ?::date) THEN 0
                        WHEN (?::date IS NOT NULL AND DATE(je.entry_date) > ?::date) THEN 0
                                ELSE (COALESCE(l.debit,0) - COALESCE(l.credit,0))
                            END
                        ELSE 0
                    END
                ), 0) as net
            ", [
                // status gating (ALL => '')
                $f->status,
                $f->status,

                // date gating
                $f->from,
                $f->from,
                $f->to,
                $f->to,
            ])
            ->groupBy('coa.id', 'coa.account_code', 'coa.name', 'coa.type', 'coa.is_active')
            ->orderBy('coa.account_code')
            ->get()
            ->map(function ($r): array {
                $net = $this->toFloat($r->net ?? null);

                return [
                    'account_id'    => $this->toInt($r->account_id ?? null),
                    'account_code'  => $this->toString($r->account_code ?? null),
                    'name'          => $this->toString($r->name ?? null),
                    'type'          => $this->toString($r->type ?? null),
                    'is_active'     => (bool) $r->is_active,
                    'ending_debit'  => $net > 0 ? round($net, 2) : 0.00,
                    'ending_credit' => $net < 0 ? round(abs($net), 2) : 0.00,
                ];
            });

        // 2) Hide zeros if requested
        if (!$f->showZero) {
            $agg = $agg->filter(function ($r) {
                return abs((float) $r['ending_debit']) > 0.000001
                    || abs((float) $r['ending_credit']) > 0.000001;
            })->values();
        }

        $totals = [
            'ending_debit'  => round($this->toFloat($agg->sum('ending_debit')), 2),
            'ending_credit' => round($this->toFloat($agg->sum('ending_credit')), 2),
        ];

        return [
            'filters' => $f->toFilterArray(),
            'rows'    => $agg,
            'totals'  => $totals,
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
