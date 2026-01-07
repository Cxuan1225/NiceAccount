<?php

namespace App\DTO\Accounting\Reports;

use Illuminate\Http\Request;
use App\Support\Accounting\Reports\ReportFiltersFactory as Filters;

final class ReportFiltersDTO {
    public function __construct(
        public readonly int $companyId,

        // DB filters
        public readonly ?string $from,
        public readonly ?string $to,
        public readonly string $status,      // '' means ALL, else POSTED/DRAFT/VOID

        // UI display
        public readonly string $statusRaw,   // posted/draft/void/all
        public readonly string $statusLabel, // All/Posted/Draft/Void
        public readonly bool $showZero,
    ) {
    }

    public static function fromRequest(Request $request, int $companyId) : self {
        $range    = Filters::dateRange($request);
        $status   = Filters::status($request);
        $showZero = Filters::boolean($request, 'show_zero', 'showZero', false);

        return new self(
            companyId: $companyId,
            from: $range['fromDb'],
            to: $range['toDb'],
            status: (string) $status['status'],
            statusRaw: (string) $status['statusRaw'],
            statusLabel: (string) $status['statusLabel'],
            showZero: $showZero,
        );
    }

    /**
     * ✅ SINGLE SOURCE OF TRUTH FOR FILTER KEYS (snake_case)
     */
    public function toFilterArray() : array {
        return [
            'from'         => $this->from ?? '',
            'to'           => $this->to ?? '',
            'status'       => $this->statusRaw,
            'status_label' => $this->statusLabel,
            'show_zero'    => $this->showZero,
        ];
    }
}
