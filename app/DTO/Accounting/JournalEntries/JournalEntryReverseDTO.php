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
        $reverseDate = (string) ($request->input('entry_date') ?: now()->toDateString());

        return new self(
            companyId: $companyId,
            journalEntryId: $journalEntryId,
            reverseDate: $reverseDate,
            memo: $request->input('memo') ?: null,
            userId: (int) (auth()->id() ?: 0),
        );
    }
}
