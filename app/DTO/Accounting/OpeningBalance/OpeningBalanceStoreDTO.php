<?php

namespace App\DTO\Accounting\OpeningBalance;

use Illuminate\Http\Request;

class OpeningBalanceStoreDTO {
    public int     $companyId;
    public string  $entryDate;
    public ?string $memo;

    /** @var array<int, array{account_id:int, amount:float}> */
    public array $lines;

    public function __construct(int $companyId, string $entryDate, ?string $memo, array $lines) {
        $this->companyId = $companyId;
        $this->entryDate = $entryDate;
        $this->memo      = $memo;
        $this->lines     = $lines;
    }

    public static function fromRequest(Request $request, int $companyId) : self {
        $data = $request->validated();

        $lines = array_map(function ($l) {
            return [
                'account_id' => (int) $l['account_id'],
                'amount'     => (float) $l['amount'],
            ];
        }, $data['lines'] ?? []);

        return new self(
            $companyId,
            (string) $data['entry_date'],
            isset($data['memo']) ? (string) $data['memo'] : null,
            $lines,
        );
    }
}
