<?php

namespace App\DTO\Accounting\PostingPeriods;

use App\Http\Requests\Accounting\PostingPeriods\PostingPeriodBulkRequest;

class PostingPeriodBulkDTO {
    /**
     * @param int[] $ids
     */
    public function __construct(
        public int $companyId,
        public array $ids,
        public ?int $userId,
    ) {
    }

    public static function fromRequest(PostingPeriodBulkRequest $request, int $companyId) : self {
        $ids = [];
        $rawIds = $request->input('ids', []);
        if (is_array($rawIds)) {
            foreach ($rawIds as $id) {
                if (is_int($id)) {
                    $ids[] = $id;
                    continue;
                }
                if (is_string($id) || is_float($id)) {
                    $ids[] = (int) $id;
                }
            }
        }

        return new self(
            companyId: $companyId,
            ids: $ids,
            userId: $request->user()?->id ? (int) $request->user()->id : null,
        );
    }
}
