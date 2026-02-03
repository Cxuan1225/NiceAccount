<?php

namespace App\DTO\Accounting\ChartOfAccounts;

use Illuminate\Http\Request;

class CoaIndexFiltersDTO {
    public function __construct(
        public readonly int $companyId,
        public readonly string $q,
        public readonly ?string $type,
        public readonly int $perPage,
    ) {
    }

    public static function fromRequest(Request $request, int $companyId) : self {
        $q    = trim((string) $request->query('q', ''));
        $type = $request->query('type');

        return new self(
            companyId: $companyId,
            q: $q,
            type: $type ? strtoupper(trim((string) $type)) : null,
            perPage: (int) $request->query('per_page', 20),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toFiltersArray() : array {
        return [
            'q'        => $this->q,
            'type'     => $this->type,
            'per_page' => $this->perPage,
        ];
    }
}
