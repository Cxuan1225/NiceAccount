<?php

namespace App\Services\Accounting\OpeningBalance;

use App\DTO\Accounting\OpeningBalance\OpeningBalanceStoreDTO;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OpeningBalanceService {
    public function create(OpeningBalanceStoreDTO $dto) : JournalEntry {
        $companyId = $dto->companyId;

        // load accounts once (type/name)
        $accountIds = collect($dto->lines)->pluck('account_id')->unique()->values();

        $accounts = ChartOfAccount::query()
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->whereIn('id', $accountIds)
            ->get([ 'id', 'type', 'name', 'account_code' ])
            ->keyBy('id');

        // OBE: prefer 3200, fallback 3000
        $obe = ChartOfAccount::query()
            ->where('company_id', $companyId)
            ->whereIn('account_code', [ '3200', '3000' ])
            ->orderByRaw("CASE WHEN account_code = '3200' THEN 0 ELSE 1 END")
            ->first([ 'id', 'name', 'account_code' ]);

        if (!$obe) {
            throw new RuntimeException('Opening Balance Equity account not found (3200/3000).');
        }

        $debitTotal  = 0.0;
        $creditTotal = 0.0;
        $jeLines     = [];

        foreach ($dto->lines as $line) {
            $amount = round((float) $line['amount'], 2);
            if ($amount <= 0) {
                continue;
            }

            $acc = $accounts->get((int) $line['account_id']);
            if (!$acc) {
                continue; // should not happen after validation
            }

            [ $debit, $credit ] = $this->mapOpeningAmountToDrCr((string) $acc->type, $amount);

            $debitTotal  += $debit;
            $creditTotal += $credit;

            $jeLines[] = [
                'company_id'  => $companyId,
                'account_id'  => (int) $acc->id,
                'debit'       => $debit,
                'credit'      => $credit,
                'description' => 'Opening Balance - ' . (string) $acc->name,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        if (count($jeLines) === 0) {
            throw new RuntimeException('Please enter at least one amount.');
        }

        // auto-balance
        $diff = round($debitTotal - $creditTotal, 2);

        if ($diff > 0) {
            $jeLines[]    = [
                'company_id'  => $companyId,
                'account_id'  => (int) $obe->id,
                'debit'       => 0,
                'credit'      => $diff,
                'description' => 'Opening Balance Equity (auto)',
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
            $creditTotal += $diff;
        } elseif ($diff < 0) {
            $need        = abs($diff);
            $jeLines[]   = [
                'company_id'  => $companyId,
                'account_id'  => (int) $obe->id,
                'debit'       => $need,
                'credit'      => 0,
                'description' => 'Opening Balance Equity (auto)',
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
            $debitTotal += $need;
        }

        if (round($debitTotal, 2) !== round($creditTotal, 2)) {
            throw new RuntimeException('Opening balance is not balanced. Please review your amounts.');
        }

        return DB::transaction(function () use ($companyId, $dto, $jeLines) {
            $je = JournalEntry::create([
                'company_id'   => $companyId,
                'entry_date'   => $dto->entryDate,
                'reference_no' => 'OPENING',
                'memo'         => $dto->memo ?: 'Opening Balance',
                'source_type'  => 'opening_balance',
                'source_id'    => null,
                'status'       => 'POSTED',
            ]);

            foreach ($jeLines as &$l) {
                $l['journal_entry_id'] = $je->id;
            }
            unset($l);

            JournalEntryLine::insert($jeLines);

            return $je;
        });
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function mapOpeningAmountToDrCr(string $accountType, float $amount) : array {
        if (in_array($accountType, [ 'ASSET', 'EXPENSE' ], true)) {
            return [ $amount, 0.0 ];
        }
        return [ 0.0, $amount ];
    }
}
