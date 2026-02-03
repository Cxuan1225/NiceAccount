<?php

namespace App\DTO\Accounting\JournalEntries;

use Illuminate\Http\Request;

class JournalEntryIndexFiltersDTO {
    public function __construct(
        public readonly int $companyId,
        public readonly string $q,
        public readonly int $perPage,
    ) {
    }

    public static function fromRequest(Request $request, int $companyId) : self {
        $q = trim((string) $request->query('q', ''));

        return new self(
            companyId: $companyId,
            q: $q,
            perPage: (int) $request->query('per_page', 20),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toFiltersArray() : array {
        return [
            'q'        => $this->q,
            'per_page' => $this->perPage,
        ];
    }
}
