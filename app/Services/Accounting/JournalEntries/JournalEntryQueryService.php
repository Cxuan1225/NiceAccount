<?php

namespace App\Services\Accounting\JournalEntries;

use App\Models\Accounting\JournalEntry;

class JournalEntryQueryService {
    /**
     * @return array{
     *   entry:array{
     *     id:int,
     *     company_id:int,
     *     entry_date:string,
     *     reference_no:string|null,
     *     memo:string|null,
     *     status:string,
     *     source_type:string|null,
     *     source_id:int|null,
     *     created_at:string,
     *     updated_at:string
     *   },
     *   lines:\Illuminate\Support\Collection<int, array<string, mixed>>,
     *   total:array{debit:float, credit:float, balanced:bool}
     * }
     */
    public function show(int $companyId, int $journalEntryId) : array {
        $je = JournalEntry::query()
            ->where('company_id', $companyId)
            ->with([
                'lines.account:id,account_code,name,type', // type optional, but useful
            ])
            ->findOrFail($journalEntryId);

        $lines = $je->lines->map(function ($l) {
            $debitRaw = $l->debit ?? null;
            $creditRaw = $l->credit ?? null;
            $debit = is_numeric($debitRaw) ? (float) $debitRaw : 0.0;
            $credit = is_numeric($creditRaw) ? (float) $creditRaw : 0.0;

            /** @var array<string, mixed> $line */
            $line = [
                'id'           => (int) $l->id,
                'account_id'   => (int) $l->account_id,
                'account_code' => $l->account?->account_code,
                'account_name' => $l->account?->name,
                'account_type' => $l->account?->type,
                'description'  => $l->description,

                // since casts are decimal:2 (string), force numeric for JSON/UI
                'debit'        => $debit,
                'credit'       => $credit,
            ];

            return $line;
        });

        $sumDebit = $lines->sum('debit');
        $sumCredit = $lines->sum('credit');
        $totalDebit  = round(is_numeric($sumDebit) ? (float) $sumDebit : 0.0, 2);
        $totalCredit = round(is_numeric($sumCredit) ? (float) $sumCredit : 0.0, 2);

        $entryDate = $je->entry_date->format('Y-m-d');
        $createdAt = $je->created_at?->format('Y-m-d H:i:s') ?? '';
        $updatedAt = $je->updated_at?->format('Y-m-d H:i:s') ?? '';

        return [
            'entry' => [
                'id'           => (int) $je->id,
                'company_id'   => (int) $je->company_id,
                'entry_date'   => $entryDate,
                'reference_no' => $je->reference_no,
                'memo'         => $je->memo,
                'status'       => $je->status,
                'source_type'  => $je->source_type,
                'source_id'    => $je->source_id,
                'created_at'   => $createdAt,
                'updated_at'   => $updatedAt,
            ],
            'lines' => $lines,
            'total' => [
                'debit'    => $totalDebit,
                'credit'   => $totalCredit,
                'balanced' => abs($totalDebit - $totalCredit) < 0.00001,
            ],
        ];
    }
}
