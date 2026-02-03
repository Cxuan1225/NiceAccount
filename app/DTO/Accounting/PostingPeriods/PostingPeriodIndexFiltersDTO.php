<?php

namespace App\DTO\Accounting\PostingPeriods;

use App\Http\Requests\Accounting\PostingPeriods\PostingPeriodIndexRequest;

class PostingPeriodIndexFiltersDTO {
    public function __construct(
        public int $companyId,
        public ?int $financialYearId,
    ) {
    }

    public static function fromRequest(PostingPeriodIndexRequest $request, int $companyId) : self {
        $fyIdRaw = $request->input('financial_year_id');
        $fyId = null;
        if ($fyIdRaw !== null && $fyIdRaw !== '' && is_numeric($fyIdRaw)) {
            $fyId = (int) $fyIdRaw;
        }

        return new self(
            companyId: $companyId,
            financialYearId: $fyId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toFiltersArray() : array {
        return [
            'financial_year_id' => $this->financialYearId,
        ];
    }
}
