<?php

namespace App\DTOs\Security;

use Illuminate\Http\Request;

class UserIndexFiltersDTO
{
    public function __construct(
        public readonly string $q,
        public readonly ?int $companyId,
        public readonly int $perPage,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $companyId = $request->query('company_id');
        $companyId = $companyId === null || $companyId === '' ? null : (int) $companyId;

        return new self(
            q: (string) $request->query('q', ''),
            companyId: $companyId,
            perPage: (int) $request->query('per_page', 15),
        );
    }
}
