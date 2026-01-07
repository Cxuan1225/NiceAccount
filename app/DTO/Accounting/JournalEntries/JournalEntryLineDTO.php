<?php

namespace App\DTO\Accounting\JournalEntries;

class JournalEntryLineDTO {
    public function __construct(
        public readonly int $accountId,
        public readonly ?string $description,
        public readonly float $debit,
        public readonly float $credit,
    ) {
    }

    public function toCreateArray(int $companyId, int $journalEntryId) : array {
        return [
            'company_id'       => $companyId,
            'journal_entry_id' => $journalEntryId,
            'account_id'       => $this->accountId,
            'debit'            => $this->debit,
            'credit'           => $this->credit,
            'description'      => $this->description,
        ];
    }
}
