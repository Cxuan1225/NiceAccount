<?php

namespace Database\Seeders;

use App\Models\Accounting\ChartOfAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run() : void {
        $companyId = 1; // default company

        $accounts = [
            // ===== ASSETS =====
            [ '1000', 'Cash', 'ASSET' ],
            [ '1010', 'Bank', 'ASSET' ],
            [ '1100', 'Accounts Receivable', 'ASSET' ],
            [ '1200', 'Prepaid Expenses', 'ASSET' ],

            // ===== LIABILITIES =====
            [ '2000', 'Accounts Payable', 'LIABILITY' ],
            [ '2100', 'Tax Payable', 'LIABILITY' ],

            // ===== EQUITY =====
            [ '3000', 'Owner Capital', 'EQUITY' ],
            [ '3100', 'Retained Earnings', 'EQUITY' ],
            [ '3200', 'Opening Balance Equity', 'EQUITY' ],

            // ===== INCOME =====
            [ '4000', 'Sales Revenue', 'INCOME' ],
            [ '4100', 'Other Income', 'INCOME' ],

            // ===== EXPENSES =====
            [ '5000', 'Cost of Goods Sold', 'EXPENSE' ],
            [ '5100', 'Office Expenses', 'EXPENSE' ],
            [ '5200', 'Utilities Expense', 'EXPENSE' ],
            [ '5300', 'Marketing Expense', 'EXPENSE' ],
        ];

        foreach ($accounts as [ $code, $name, $type ]) {
            ChartOfAccount::firstOrCreate(
                [
                    'company_id'   => $companyId,
                    'account_code' => $code,
                ],
                [
                    'name'      => $name,
                    'type'      => $type,
                    'is_active' => true,
                ],
            );
        }

    }
}
