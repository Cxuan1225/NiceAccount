<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use Illuminate\Http\Request;

class LedgerController extends BaseAccountingController
{
    public function show(Request $request, ChartOfAccount $account)
    {
        abort_unless($account->company_id == $this->companyId, 404);

        $lines = JournalEntryLine::query()
            ->where('company_id', $this->companyId)
            ->where('account_id', $account->id)
            ->whereHas('entry', function ($q) {
                $q->where('status', 'POSTED');
            })
            ->with(['entry:id,entry_date,reference_no'])
            // order by the entry_date via subquery (works without join)
            ->orderBy(
                JournalEntry::select('entry_date')
                    ->whereColumn('journal_entries.id', 'journal_entry_lines.journal_entry_id')
            )
            ->orderBy('id')
            ->get();

        $balance = 0;

        $ledger = $lines->map(function ($l) use (&$balance) {
            $balance += ($l->debit ?? 0) - ($l->credit ?? 0);

            return [
                'date' => $l->entry?->entry_date?->format('d-m-Y'),
                'reference' => $l->entry?->reference_no,
                'description' => $l->description,
                'debit' => (float) $l->debit,
                'credit' => (float) $l->credit,
                'balance' => $balance,
            ];
        });

        return response()->json([
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'code' => $account->account_code ?? null,
            ],
            'ledger' => $ledger,
            'closing_balance' => $balance,
        ]);
    }
}
