<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TrialBalanceExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private Collection $rows,
        private array $filters,
        private array $totals
    ) {
    }

    public function collection()
    {
        return $this->rows;
    }

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

    public function map($row): array
    {
        return [
            $row['account_code'],
            $row['name'],
            $row['type'],
            number_format((float) $row['ending_debit'], 2, '.', ''),
            number_format((float) $row['ending_credit'], 2, '.', ''),
        ];
    }
}
