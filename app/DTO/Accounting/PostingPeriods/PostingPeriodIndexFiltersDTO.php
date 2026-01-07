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
        $fyId = $request->input('financial_year_id');
        $fyId = is_null($fyId) ? null : (int) $fyId;

        return new self(
            companyId: $companyId,
            financialYearId: $fyId,
        );
    }

    public function toFiltersArray() : array {
        return [
            'financial_year_id' => $this->financialYearId,
        ];
    }
}
