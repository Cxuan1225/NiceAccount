<?php

namespace App\DTOs\Company;

use Illuminate\Http\Request;

class CompanyIndexFiltersDTO
{
    public function __construct(
        public readonly string $q,
        public readonly int $perPage,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            q: (string) $request->query('q', ''),
            perPage: (int) $request->query('per_page', 15),
        );
    }
}
