<?php

namespace App\Services\Accounting\Reports;

use App\Support\Accounting\NormalBalance;
use Illuminate\Support\Facades\DB;

class GeneralLedgerReportService {
    public function build(
        int $companyId,
        ?int $accountId,
        ?string $from,
        ?string $to,
        string $status,      // '' = ALL, else POSTED/DRAFT/VOID
        string $statusRaw,   // for UI
        string $statusLabel, // for UI
        bool $showZero,
    ) : array {
        // If no account selected, return empty but keep filters for UI
        if (!$accountId) {
            return [
                'filters' => [
                    'account_id'   => null,
                    'from'         => $from,
                    'to'           => $to,
                    'status'       => $statusRaw,
                    'status_label' => $statusLabel,
                    'show_zero'    => $showZero,
                ],
                'account' => null,
                'opening' => [ 'debit' => 0.00, 'credit' => 0.00, 'balance' => 0.00 ],
                'rows'    => [],
                'totals'  => [ 'debit' => 0.00, 'credit' => 0.00, 'ending_balance' => 0.00 ],
            ];
        }

        // 1) Load account and verify company
        $coa = DB::table('chart_of_accounts')
            ->select('id', 'account_code', 'name', 'type')
            ->where('company_id', $companyId)
            ->where('id', $accountId)
            ->first();

        if (!$coa) {
            // account not found / not belonging to company
            return [
                'filters'         => [
                    'account_id'   => $accountId,
                    'from'         => $from,
                    'to'           => $to,
                    'status'       => $statusRaw,
                    'status_label' => $statusLabel,
                    'show_zero'    => $showZero,
                ],
                'selectedAccount' => null,
                'opening'         => [ 'debit' => 0.00, 'credit' => 0.00, 'balance' => 0.00 ],
                'rows'            => [],
                'totals'          => [ 'debit' => 0.00, 'credit' => 0.00, 'ending_balance' => 0.00 ],
                'error'           => 'Account not found.',
            ];
        }

        $type = (string) $coa->type;

        $normal = fn (float $debit, float $credit) => NormalBalance::amount($type, $debit, $credit);

        // 2) Opening sums: < from (if from exists)
        $openingDebit  = 0.0;
        $openingCredit = 0.0;

        if ($from) {
            $open = DB::table('journal_entry_lines as l')
                ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
                ->where('l.company_id', $companyId)
                ->where('e.company_id', $companyId)
                ->where('l.account_id', $accountId)
                ->when($status !== '', fn ($q) => $q->whereRaw('UPPER(e.status) = ?', [ $status ]))
                ->whereDate('e.entry_date', '<', $from)
                ->selectRaw('COALESCE(SUM(l.debit),0) as debit_sum, COALESCE(SUM(l.credit),0) as credit_sum')
                ->first();

            $openingDebit  = $open ? (float) $open->debit_sum : 0.0;
            $openingCredit = $open ? (float) $open->credit_sum : 0.0;
        }

        $openingBalance = round($normal($openingDebit, $openingCredit), 2);

        // 3) Period rows: from..to (if missing, show all; but usually you pass from/to)
        $q = DB::table('journal_entry_lines as l')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->select([
                'e.entry_date',
                'e.reference_no',
                'e.memo',
                'e.status',
                'l.debit',
                'l.credit',
                'l.id as line_id',
                'e.id as journal_entry_id',
            ])
            ->where('l.company_id', $companyId)
            ->where('e.company_id', $companyId)
            ->where('l.account_id', $accountId)
            ->when($status !== '', fn ($qq) => $qq->whereRaw('UPPER(e.status) = ?', [ $status ]))
            ->when($from, fn ($qq) => $qq->whereDate('e.entry_date', '>=', $from))
            ->when($to, fn ($qq) => $qq->whereDate('e.entry_date', '<=', $to))
            ->orderBy('e.entry_date')
            ->orderBy('l.id');

        $lines = $q->get();

        // 4) Map rows + running balance
        $running = $openingBalance;

        $rows = collect($lines)->map(function ($r) use (&$running, $normal) {
            $debit  = (float) ($r->debit ?? 0);
            $credit = (float) ($r->credit ?? 0);

            $movement = $normal($debit, $credit);
            $running  = round($running + $movement, 2);

            return [
                'entry_date'       => (string) $r->entry_date,
                'reference_no'     => $r->reference_no ?? null,
                'memo'             => $r->memo ?? null,
                'source_type'      => null,
                'source_id'        => null,
                'line_description' => null,
                'debit'            => round($debit, 2),
                'credit'           => round($credit, 2),
                'running_balance'  => $running,
                'journal_entry_id' => (int) $r->journal_entry_id,
                'line_id'          => (int) $r->line_id,
            ];

        });

        // Optionally hide when all rows zero (rare for GL, but keep flag)
        if (!$showZero) {
            $rows = $rows->filter(fn ($r) => abs((float) $r['debit']) > 0.000001 || abs((float) $r['credit']) > 0.000001)->values();
        }

        $totalDebit  = round($rows->sum('debit'), 2);
        $totalCredit = round($rows->sum('credit'), 2);

        // ending balance is last running, or opening if no rows
        $endingBalance = $rows->count()
            ? (float) $rows->last()['running_balance']
            : $openingBalance;

        return [
            'filters' => [
                'account_id'   => (int) $coa->id,
                'from'         => $from,
                'to'           => $to,
                'status'       => $statusRaw,
                'status_label' => $statusLabel,
                'show_zero'    => $showZero,
            ],
            'account' => [
                'id'           => (int) $coa->id,
                'account_code' => (string) $coa->account_code,
                'name'         => (string) $coa->name,
                'type'         => (string) $coa->type,
                'normal_side'  => NormalBalance::side($type),
            ],
            'opening' => [
                'debit'   => round($openingDebit, 2),
                'credit'  => round($openingCredit, 2),
                'balance' => $openingBalance,
            ],
            'rows'    => $rows,
            'totals'  => [
                'periodDebit'    => $totalDebit,
                'periodCredit'   => $totalCredit,
                'closingBalance' => round($endingBalance, 2),
            ],

        ];
    }
}
