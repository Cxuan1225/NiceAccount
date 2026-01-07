<?php

namespace App\Services\Accounting\JournalEntries;

use App\Models\Accounting\JournalEntry;

class JournalEntryQueryService {
    public function show(int $companyId, int $journalEntryId) : array {
        $je = JournalEntry::query()
            ->where('company_id', $companyId)
            ->with([
                'lines.account:id,account_code,name,type', // type optional, but useful
            ])
            ->findOrFail($journalEntryId);

        $lines = $je->lines->map(function ($l) {
            return [
                'id'           => (int) $l->id,
                'account_id'   => (int) $l->account_id,
                'account_code' => $l->account?->account_code,
                'account_name' => $l->account?->name,
                'account_type' => $l->account?->type,
                'description'  => $l->description,

                // since casts are decimal:2 (string), force numeric for JSON/UI
                'debit'        => (float) $l->debit,
                'credit'       => (float) $l->credit,
            ];
        });

        $totalDebit  = round((float) $lines->sum('debit'), 2);
        $totalCredit = round((float) $lines->sum('credit'), 2);

        return [
            'entry' => [
                'id'           => (int) $je->id,
                'company_id'   => (int) $je->company_id,
                'entry_date'   => $je->entry_date?->format('Y-m-d'),
                'reference_no' => $je->reference_no,
                'memo'         => $je->memo,
                'status'       => $je->status,
                'source_type'  => $je->source_type,
                'source_id'    => $je->source_id,
                'created_at'   => $je->created_at?->format('Y-m-d H:i:s'),
                'updated_at'   => $je->updated_at?->format('Y-m-d H:i:s'),
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
