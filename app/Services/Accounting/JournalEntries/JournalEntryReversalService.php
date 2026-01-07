<?php

namespace App\Services\Accounting\JournalEntries;

use App\DTO\Accounting\JournalEntries\JournalEntryReverseDTO;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use App\Services\Accounting\PostingPeriods\PostingPeriodService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalEntryReversalService {
    public function reverse(JournalEntryReverseDTO $dto) : JournalEntry {
        $entry = JournalEntry::query()
            ->where('company_id', $dto->companyId)
            ->with([ 'lines' ]) // lines must exist
            ->findOrFail($dto->journalEntryId);

        // ---------- Business rules ----------
        if (($entry->status ?? null) !== 'POSTED') {
            throw ValidationException::withMessages([
                'status' => 'Only POSTED journal entries can be reversed.',
            ]);
        }

        // If you have "reversed_at" column:
        if (!empty($entry->reversed_at)) {
            throw ValidationException::withMessages([
                'reversed_at' => 'This journal entry has already been reversed.',
            ]);
        }

        // Prevent reversing a reversal (optional safety)
        if (($entry->source_type ?? null) === 'REVERSAL') {
            throw ValidationException::withMessages([
                'source_type' => 'Reversal entries cannot be reversed again.',
            ]);
        }

        // Period lock checks (both original date and reversal date)
        PostingPeriodService::assertCanPost($dto->companyId, Carbon::parse($entry->entry_date));
        PostingPeriodService::assertCanPost($dto->companyId, Carbon::parse($dto->reverseDate));

        // ---------- Transaction ----------
        return DB::transaction(function () use ($dto, $entry) {

            $ref = $entry->reference_no ?: ('JE-' . $entry->id);

            $memo = $dto->memo ?: ('Reversal of ' . $ref . ($entry->memo ? (' - ' . $entry->memo) : ''));

            $reversal = JournalEntry::create([
                'company_id'     => $dto->companyId,
                'entry_date'     => $dto->reverseDate,
                'reference_no'   => 'REV-' . $ref,
                'memo'           => $memo,
                'status'         => 'POSTED',
                'source_type'    => 'REVERSAL',

                // ✅ If your table has these columns, keep them:
                'source_id'      => $entry->id,        // optional link
                'reversal_of_id' => $entry->id,        // optional link (if you created this column)
            ]);

            foreach ($entry->lines as $l) {
                /** @var JournalEntryLine $l */
                JournalEntryLine::create([
                    'company_id'       => $dto->companyId,
                    'journal_entry_id' => $reversal->id,
                    'account_id'       => $l->account_id,
                    'description'      => $l->description ?? null,

                    // reverse amounts
                    'debit'            => (float) ($l->credit ?? 0),
                    'credit'           => (float) ($l->debit ?? 0),
                ]);
            }

            // ✅ Mark original as reversed (if you have these columns)
            $entry->update([
                'reversed_at' => now(),
                'reversed_by' => $dto->userId ?: null,
            ]);

            return $reversal;
        });
    }
}
