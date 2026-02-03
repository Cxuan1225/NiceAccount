<?php

namespace App\Services\Accounting\PostingPeriods;

use App\DTO\Accounting\PostingPeriods\PostingPeriodIndexFiltersDTO;
use App\Models\Accounting\FinancialYear;
use App\Models\Accounting\PostingPeriod;

class PostingPeriodQueryService {
    /**
     * @return array<int, array<string, mixed>>
     */
    public function indexData(PostingPeriodIndexFiltersDTO $filters) : array {
        $q = FinancialYear::query()
            ->where('company_id', $filters->companyId)
            ->orderByDesc('start_date')
            ->with([
                'periods' => function ($p) use ($filters) {
                    if ($p instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
                        $p->where('company_id', $filters->companyId)
                            ->orderBy('period_start');
                    }
                }
            ]);

        if ($filters->financialYearId) {
            $q->where('id', $filters->financialYearId);
        }

        return $q->get()
            ->map(function (FinancialYear $fy) {
                $startDate = $fy->start_date->format('d-m-Y');
                $endDate = $fy->end_date->format('d-m-Y');

                $periods = $fy->periods->map(function (PostingPeriod $p) {
                    $periodStart = $p->period_start->format('d-m-Y');
                    $periodEnd = $p->period_end->format('d-m-Y');
                    $lockedAt = $p->locked_at?->format('d-m-Y H:i:s') ?? '';
                    $lockedByRaw = $p->getAttribute('locked_by');
                    $lockedBy = is_numeric($lockedByRaw) ? (int) $lockedByRaw : null;

                    /** @var array<string, mixed> $row */
                    $row = [
                        'id'           => (int) $p->id,
                        'period_start' => $periodStart,
                        'period_end'   => $periodEnd,
                        'is_locked'    => (bool) $p->is_locked,
                        'locked_at'    => $lockedAt,
                        'locked_by'    => $lockedBy,
                    ];

                    return $row;
                })->values();

                $name = is_string($fy->name ?? null) ? $fy->name : '';
                $row = [
                    'id'         => (int) $fy->id,
                    'name'       => $name,
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                    'is_closed'  => (bool) $fy->is_closed,
                    'periods'    => $periods,
                ];

                return $row;
            })
            ->values()
            ->all();
    }
}
