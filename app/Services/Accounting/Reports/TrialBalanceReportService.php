<?php

namespace App\Services\Accounting\Reports;

use App\DTO\Accounting\Reports\ReportFiltersDTO;
use Illuminate\Support\Facades\DB;

class TrialBalanceReportService {
    public function build(ReportFiltersDTO $f) : array {
        // 1) Aggregate per account with COA joined (1 query)
        $agg = DB::table('chart_of_accounts as coa')
            ->leftJoin('journal_entry_lines as l', function ($join) use ($f) {
                $join->on('l.account_id', '=', 'coa.id')
                    ->where('l.company_id', '=', $f->companyId);
            })
            ->leftJoin('journal_entries as je', function ($join) use ($f) {
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
                                WHEN (? IS NOT NULL AND DATE(je.entry_date) < DATE(?)) THEN 0
                                WHEN (? IS NOT NULL AND DATE(je.entry_date) > DATE(?)) THEN 0
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
            ->map(function ($r) {
                $net = (float) $r->net;

                return [
                    'account_id'    => (int) $r->account_id,
                    'account_code'  => (string) $r->account_code,
                    'name'          => (string) $r->name,
                    'type'          => (string) $r->type,
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
            'ending_debit'  => round($agg->sum('ending_debit'), 2),
            'ending_credit' => round($agg->sum('ending_credit'), 2),
        ];

        return [
            'filters' => $f->toFilterArray(),
            'rows'    => $agg,
            'totals'  => $totals,
        ];
    }
}
