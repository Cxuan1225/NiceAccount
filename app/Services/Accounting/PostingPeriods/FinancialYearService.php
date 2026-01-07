<?php

namespace App\Services\Accounting\PostingPeriods;

use App\Models\Accounting\FinancialYear;
use App\Models\Accounting\PostingPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialYearService {
    public static function createWithPeriods(int $companyId, string $name, Carbon $start, Carbon $end) : FinancialYear {
        if ($end->lt($start)) {
            throw ValidationException::withMessages([
                'end_date' => 'End date must be after start date.',
            ]);
        }

        // overlap check (same company)
        $overlap = FinancialYear::query()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [ $start->toDateString(), $end->toDateString() ])
                    ->orWhereBetween('end_date', [ $start->toDateString(), $end->toDateString() ])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->whereDate('start_date', '<=', $start->toDateString())
                            ->whereDate('end_date', '>=', $end->toDateString());
                    });
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_date' => 'Financial year overlaps with an existing financial year.',
            ]);
        }

        return DB::transaction(function () use ($companyId, $name, $start, $end) {
            $fy = FinancialYear::create([
                'company_id' => $companyId,
                'name'       => $name,
                'start_date' => $start->toDateString(),
                'end_date'   => $end->toDateString(),
                'is_closed'  => false,
            ]);

            // Generate monthly periods from start -> end
            $cursor = $start->copy()->startOfDay();
            $endDay = $end->copy()->startOfDay();

            while ($cursor->lte($endDay)) {
                $pStart = $cursor->copy();

                // end of that month, but not beyond FY end
                $pEnd = $cursor->copy()->endOfMonth();
                if ($pEnd->gt($endDay))
                    $pEnd = $endDay->copy();

                PostingPeriod::create([
                    'company_id'        => $companyId,
                    'financial_year_id' => $fy->id,
                    'period_start'      => $pStart->toDateString(),
                    'period_end'        => $pEnd->toDateString(),
                    'is_locked'         => false,
                ]);

                $cursor = $cursor->addMonth()->startOfMonth();
            }

            return $fy;
        });
    }
}
