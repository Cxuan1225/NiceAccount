<?php

namespace App\DTO\Accounting\OpeningBalance;

use Illuminate\Foundation\Http\FormRequest;

class OpeningBalanceStoreDTO {
    public int     $companyId;
    public string  $entryDate;
    public ?string $memo;

    /** @var array<int, array{account_id:int, amount:float}> */
    public array $lines;

    /**
     * @param array<int, array{account_id:int, amount:float}> $lines
     */
    public function __construct(int $companyId, string $entryDate, ?string $memo, array $lines) {
        $this->companyId = $companyId;
        $this->entryDate = $entryDate;
        $this->memo      = $memo;
        $this->lines     = $lines;
    }

    public static function fromRequest(FormRequest $request, int $companyId) : self {
        $data = $request->validated();

        $lines = [];
        $rawLines = $data['lines'] ?? [];
        if (is_array($rawLines)) {
            foreach ($rawLines as $l) {
                if (!is_array($l)) {
                    continue;
                }

                $accountIdRaw = $l['account_id'] ?? 0;
                $amountRaw = $l['amount'] ?? 0;

                $lines[] = [
                    'account_id' => is_numeric($accountIdRaw) ? (int) $accountIdRaw : 0,
                    'amount'     => is_numeric($amountRaw) ? (float) $amountRaw : 0.0,
                ];
            }
        }

        $entryDateRaw = $data['entry_date'] ?? '';
        $entryDate = is_string($entryDateRaw) ? $entryDateRaw : '';
        $memoRaw = $data['memo'] ?? null;
        $memo = is_string($memoRaw) ? $memoRaw : null;

        return new self(
            $companyId,
            $entryDate,
            $memo,
            $lines,
        );
    }
}
