<?php

namespace App\Services\Accounting\Reports;

use App\Support\Accounting\NormalBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class GeneralLedgerReportService {
    /**
     * @return array{
     *   filters:array{account_id:int|null, from:string|null, to:string|null, status:string, status_label:string, show_zero:bool},
     *   account:array{id:int, account_code:string, name:string, type:string, normal_side:string}|null,
     *   opening:array{debit:float, credit:float, balance:float},
     *   rows:Collection<int, array<string, mixed>>,
     *   totals:array{periodDebit:float, periodCredit:float, closingBalance:float},
     *   error?:string
     * }
     */
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
            /** @var Collection<int, array<string, mixed>> $rows */
            $rows = collect();
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
                'rows'    => $rows,
                'totals'  => [ 'periodDebit' => 0.00, 'periodCredit' => 0.00, 'closingBalance' => 0.00 ],
            ];
        }

        // 1) Load account and verify company
        /** @var \stdClass|null $coa */
        $coa = DB::table('chart_of_accounts')
            ->select('id', 'account_code', 'name', 'type')
            ->where('company_id', $companyId)
            ->where('id', $accountId)
            ->first();

        if (!$coa) {
            // account not found / not belonging to company
            /** @var Collection<int, array<string, mixed>> $rows */
            $rows = collect();
            return [
                'filters'         => [
                    'account_id'   => $accountId,
                    'from'         => $from,
                    'to'           => $to,
                    'status'       => $statusRaw,
                    'status_label' => $statusLabel,
                    'show_zero'    => $showZero,
                ],
                'account'         => null,
                'opening'         => [ 'debit' => 0.00, 'credit' => 0.00, 'balance' => 0.00 ],
                'rows'            => $rows,
                'totals'          => [ 'periodDebit' => 0.00, 'periodCredit' => 0.00, 'closingBalance' => 0.00 ],
                'error'           => 'Account not found.',
            ];
        }

        $type = $this->toString($coa->type ?? null);

        $normal = fn (float $debit, float $credit) => NormalBalance::amount($type, $debit, $credit);

        // 2) Opening sums: < from (if from exists)
        $openingDebit  = 0.0;
        $openingCredit = 0.0;

        if ($from) {
            /** @var \stdClass|null $open */
            $open = DB::table('journal_entry_lines as l')
                ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
                ->where('l.company_id', $companyId)
                ->where('e.company_id', $companyId)
                ->where('l.account_id', $accountId)
                ->when($status !== '', fn ($q) => $q->whereRaw('UPPER(e.status) = ?', [ $status ]))
                ->whereDate('e.entry_date', '<', $from)
                ->selectRaw('COALESCE(SUM(l.debit),0) as debit_sum, COALESCE(SUM(l.credit),0) as credit_sum')
                ->first();

            $openingDebit  = $open ? $this->toFloat($open->debit_sum ?? null) : 0.0;
            $openingCredit = $open ? $this->toFloat($open->credit_sum ?? null) : 0.0;
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

        /** @var Collection<int, \stdClass> $lines */
        $lines = $q->get();

        // 4) Map rows + running balance
        $running = $openingBalance;

        /** @var Collection<int, array{
         *   entry_date:string,
         *   reference_no:mixed,
         *   memo:mixed,
         *   source_type:null,
         *   source_id:null,
         *   line_description:null,
         *   debit:float,
         *   credit:float,
         *   running_balance:float,
         *   journal_entry_id:int,
         *   line_id:int
         * }> $rows */
        $rows = collect($lines)->map(function ($r) use (&$running, $normal) {
            $debit  = $this->toFloat($r->debit ?? null);
            $credit = $this->toFloat($r->credit ?? null);
            $entryDate = $this->toString($r->entry_date ?? null);
            $referenceNo = $r->reference_no ?? null;
            $memo = $r->memo ?? null;
            $journalEntryId = $this->toInt($r->journal_entry_id ?? null);
            $lineId = $this->toInt($r->line_id ?? null);

            $movement = $normal($debit, $credit);
            $running  = round($running + $movement, 2);

            return [
                'entry_date'       => $entryDate,
                'reference_no'     => $referenceNo,
                'memo'             => $memo,
                'source_type'      => null,
                'source_id'        => null,
                'line_description' => null,
                'debit'            => round($debit, 2),
                'credit'           => round($credit, 2),
                'running_balance'  => $running,
                'journal_entry_id' => $journalEntryId,
                'line_id'          => $lineId,
            ];

        });

        // Optionally hide when all rows zero (rare for GL, but keep flag)
        if (!$showZero) {
            $rows = $rows->filter(function ($r) {
                $debit = $this->toFloat($r['debit']);
                $credit = $this->toFloat($r['credit']);

                return abs($debit) > 0.000001 || abs($credit) > 0.000001;
            })->values();
        }

        $totalDebit  = round($this->toFloat($rows->sum('debit')), 2);
        $totalCredit = round($this->toFloat($rows->sum('credit')), 2);

        // ending balance is last running, or opening if no rows
        $lastRow = $rows->last();
        $endingBalance = $lastRow ? $this->toFloat($lastRow['running_balance']) : $openingBalance;

        /** @var Collection<int, array<string, mixed>> $rowsGeneric */
        $rowsGeneric = $rows->map(function (array $row): array {
            return $row;
        });

        return [
            'filters' => [
                'account_id'   => $this->toInt($coa->id ?? null),
                'from'         => $from,
                'to'           => $to,
                'status'       => $statusRaw,
                'status_label' => $statusLabel,
                'show_zero'    => $showZero,
            ],
            'account' => [
                'id'           => $this->toInt($coa->id ?? null),
                'account_code' => $this->toString($coa->account_code ?? null),
                'name'         => $this->toString($coa->name ?? null),
                'type'         => $this->toString($coa->type ?? null),
                'normal_side'  => NormalBalance::side($type),
            ],
            'opening' => [
                'debit'   => round($openingDebit, 2),
                'credit'  => round($openingCredit, 2),
                'balance' => $openingBalance,
            ],
            'rows'    => $rowsGeneric,
            'totals'  => [
                'periodDebit'    => $totalDebit,
                'periodCredit'   => $totalCredit,
                'closingBalance' => round($endingBalance, 2),
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
