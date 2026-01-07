<?php

namespace App\Services\Accounting\PostingPeriods;

use App\DTO\Accounting\PostingPeriods\PostingPeriodIndexFiltersDTO;
use App\Models\Accounting\FinancialYear;

class PostingPeriodQueryService {
    public function indexData(PostingPeriodIndexFiltersDTO $filters) : array {
        $q = FinancialYear::query()
            ->where('company_id', $filters->companyId)
            ->orderByDesc('start_date')
            ->with([
                'periods' => function ($p) use ($filters) {
                    $p->where('company_id', $filters->companyId)
                        ->orderBy('period_start');
                }
            ]);

        if ($filters->financialYearId) {
            $q->where('id', $filters->financialYearId);
        }

        return $q->get()
            ->map(function ($fy) {
                return [
                    'id'         => (int) $fy->id,
                    'name'       => (string) $fy->name,
                    'start_date' => $fy->start_date?->format('d-m-Y'),
                    'end_date'   => $fy->end_date?->format('d-m-Y'),
                    'is_closed'  => (bool) $fy->is_closed,
                    'periods'    => $fy->periods->map(function ($p) {
                        return [
                            'id'           => (int) $p->id,
                            'period_start' => $p->period_start?->format('d-m-Y'),
                            'period_end'   => $p->period_end?->format('d-m-Y'),
                            'is_locked'    => (bool) $p->is_locked,
                            'locked_at'    => $p->locked_at?->format('d-m-Y H:i:s'),
                            'locked_by'    => $p->locked_by ? (int) $p->locked_by : null,
                        ];
                    })->values(),
                ];
            })
            ->values()
            ->all();
    }
}
