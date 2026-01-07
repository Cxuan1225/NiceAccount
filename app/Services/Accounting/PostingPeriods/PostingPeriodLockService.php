<?php

namespace App\Services\Accounting\PostingPeriods;

use App\DTO\Accounting\PostingPeriods\PostingPeriodBulkDTO;
use App\Models\Accounting\PostingPeriod;
use Illuminate\Support\Facades\DB;

class PostingPeriodLockService {
    public function lockOne(int $companyId, int $periodId, ?int $userId) : void {
        DB::transaction(function () use ($companyId, $periodId, $userId) {
            /** @var PostingPeriod $p */
            $p = PostingPeriod::query()
                ->where('company_id', $companyId)
                ->whereKey($periodId)
                ->lockForUpdate()
                ->firstOrFail();

            if ((bool) $p->is_locked) {
                return;
            }

            $p->lock($userId);
        });
    }

    public function unlockOne(int $companyId, int $periodId) : void {
        DB::transaction(function () use ($companyId, $periodId) {
            /** @var PostingPeriod $p */
            $p = PostingPeriod::query()
                ->where('company_id', $companyId)
                ->whereKey($periodId)
                ->with('financialYear')
                ->lockForUpdate()
                ->firstOrFail();

            if (!(bool) $p->is_locked) {
                return;
            }

            if ($p->financialYear && (bool) $p->financialYear->is_closed) {
                abort(422, 'Financial year is closed. Cannot unlock periods.');
            }

            $p->unlock();
        });
    }

    public function bulkLock(PostingPeriodBulkDTO $dto) : void {
        DB::transaction(function () use ($dto) {
            DB::table('posting_periods')
                ->where('company_id', $dto->companyId)
                ->whereIn('id', $dto->ids)
                ->where('is_locked', 0)
                ->update([
                    'is_locked'  => 1,
                    'locked_at'  => now(),
                    'locked_by'  => $dto->userId,
                    'updated_at' => now(),
                ]);
        });
    }

    public function bulkUnlock(PostingPeriodBulkDTO $dto) : void {
        DB::transaction(function () use ($dto) {
            // If you want to prevent unlock when FY closed in bulk,
            // you can join financial_years and filter is_closed = 0.
            DB::table('posting_periods')
                ->where('company_id', $dto->companyId)
                ->whereIn('id', $dto->ids)
                ->where('is_locked', 1)
                ->update([
                    'is_locked'  => 0,
                    'locked_at'  => null,
                    'locked_by'  => null,
                    'updated_at' => now(),
                ]);
        });
    }
}
