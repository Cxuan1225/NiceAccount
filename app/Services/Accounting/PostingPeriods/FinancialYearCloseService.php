<?php

namespace App\Services\Accounting\PostingPeriods;

use App\Models\Accounting\FinancialYear;
use Illuminate\Support\Facades\DB;

class FinancialYearCloseService {
    public function close(int $companyId, int $financialYearId) : void {
        DB::transaction(function () use ($companyId, $financialYearId) {
            /** @var FinancialYear $fy */
            $fy = FinancialYear::query()
                ->where('company_id', $companyId)
                ->whereKey($financialYearId)
                ->lockForUpdate()
                ->firstOrFail();

            if ((bool) $fy->is_closed) {
                return; // already closed
            }

            // Lock check (race-safe enough because FY locked)
            $hasOpen = $fy->periods()
                ->where('company_id', $companyId)
                ->where('is_locked', false)
                ->exists();

            if ($hasOpen) {
                abort(422, 'You must lock all posting periods before closing the financial year.');
            }

            $fy->update([
                'is_closed' => 1,
            ]);
        });
    }
}
