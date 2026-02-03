<?php

namespace App\DTO\Accounting\JournalEntries;

use Illuminate\Http\Request;

class JournalEntryReverseDTO {
    public function __construct(
        public readonly int $companyId,
        public readonly int $journalEntryId,
        public readonly string $reverseDate, // YYYY-MM-DD
        public readonly ?string $memo,
        public readonly int $userId,
    ) {
    }

    public static function fromRequest(Request $request, int $companyId, int $journalEntryId) : self {
        $entryDate = $request->input('entry_date');
        $reverseDate = is_string($entryDate) && $entryDate !== ''
            ? $entryDate
            : now()->toDateString();
        $memoRaw = $request->input('memo');
        $memo = is_string($memoRaw) && $memoRaw !== '' ? $memoRaw : null;

        return new self(
            companyId: $companyId,
            journalEntryId: $journalEntryId,
            reverseDate: $reverseDate,
            memo: $memo,
            userId: (int) (auth()->id() ?: 0),
        );
    }
}
