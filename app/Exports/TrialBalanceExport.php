<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<array<string, mixed>>
 */
class TrialBalanceExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        /** @var Collection<int, array<string, mixed>> */
        private Collection $rows
    ) {
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function collection(): Collection
    {
        return $this->rows;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Account Code',
            'Account Name',
            'Type',
            'Ending Debit',
            'Ending Credit',
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<int, string>
     */
    public function map($row): array
    {
        $accountCode = $row['account_code'] ?? '';
        $name = $row['name'] ?? '';
        $type = $row['type'] ?? '';
        $endingDebit = $row['ending_debit'] ?? 0;
        $endingCredit = $row['ending_credit'] ?? 0;

        return [
            is_string($accountCode) ? $accountCode : '',
            is_string($name) ? $name : '',
            is_string($type) ? $type : '',
            number_format(is_numeric($endingDebit) ? (float) $endingDebit : 0.0, 2, '.', ''),
            number_format(is_numeric($endingCredit) ? (float) $endingCredit : 0.0, 2, '.', ''),
        ];
    }
}
