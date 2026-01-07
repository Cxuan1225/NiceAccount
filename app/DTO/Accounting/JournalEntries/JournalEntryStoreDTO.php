<?php

namespace App\DTO\Accounting\JournalEntries;

use Illuminate\Http\Request;

class JournalEntryStoreDTO {
    /** @param JournalEntryLineDTO[] $lines */
    public function __construct(
        public readonly int $companyId,
        public readonly string $entryDate,       // YYYY-MM-DD
        public readonly ?string $referenceNo,
        public readonly ?string $memo,
        public readonly array $lines,
    ) {
    }

    public static function fromRequest(Request $request, int $companyId) : self {
        $rawLines = (array) $request->input('lines', []);

        $lines = array_map(function ($l) {
            $debit  = isset($l['debit']) ? (float) $l['debit'] : 0.0;
            $credit = isset($l['credit']) ? (float) $l['credit'] : 0.0;

            // normalize to 2 dp
            $debit  = round($debit, 2);
            $credit = round($credit, 2);

            return new JournalEntryLineDTO(
                accountId: (int) $l['account_id'],
                description: $l['description'] ?? null,
                debit: $debit,
                credit: $credit,
            );
        }, $rawLines);

        return new self(
            companyId: $companyId,
            entryDate: (string) $request->input('entry_date'),
            referenceNo: $request->input('reference_no') ?: null,
            memo: $request->input('memo') ?: null,
            lines: $lines,
        );
    }
}
