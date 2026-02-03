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
        $rawLines = $request->input('lines', []);
        $lines = [];

        if (is_array($rawLines)) {
            foreach ($rawLines as $l) {
                if (!is_array($l)) {
                    continue;
                }

                $debitRaw = $l['debit'] ?? 0;
                $creditRaw = $l['credit'] ?? 0;
                $debit = is_numeric($debitRaw) ? (float) $debitRaw : 0.0;
                $credit = is_numeric($creditRaw) ? (float) $creditRaw : 0.0;

                // normalize to 2 dp
                $debit  = round($debit, 2);
                $credit = round($credit, 2);

                $accountIdRaw = $l['account_id'] ?? 0;
                $accountId = is_numeric($accountIdRaw) ? (int) $accountIdRaw : 0;
                $descriptionRaw = $l['description'] ?? null;
                $description = is_string($descriptionRaw) ? $descriptionRaw : null;

                $lines[] = new JournalEntryLineDTO(
                    accountId: $accountId,
                    description: $description,
                    debit: $debit,
                    credit: $credit,
                );
            }
        }

        $entryDateRaw = $request->input('entry_date');
        $entryDate = is_string($entryDateRaw) ? $entryDateRaw : '';
        $referenceNoRaw = $request->input('reference_no');
        $referenceNo = is_string($referenceNoRaw) && $referenceNoRaw !== '' ? $referenceNoRaw : null;
        $memoRaw = $request->input('memo');
        $memo = is_string($memoRaw) && $memoRaw !== '' ? $memoRaw : null;

        return new self(
            companyId: $companyId,
            entryDate: $entryDate,
            referenceNo: $referenceNo,
            memo: $memo,
            lines: $lines,
        );
    }
}
