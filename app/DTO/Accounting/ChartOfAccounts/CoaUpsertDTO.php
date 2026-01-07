<?php

namespace App\DTO\Accounting\ChartOfAccounts;

use Illuminate\Http\Request;

class CoaUpsertDTO {
    public function __construct(
        public readonly int $companyId,
        public readonly string $accountCode,
        public readonly string $name,
        public readonly string $type,
        public readonly ?int $parentId,
        public readonly bool $isActive,
    ) {
    }

    public static function fromRequest(Request $request, int $companyId) : self {
        // request is already validated, so just normalize
        $parentId = $request->input('parent_id');
        $parentId = $parentId === null || $parentId === '' ? null : (int) $parentId;

        return new self(
            companyId: $companyId,
            accountCode: (string) $request->input('account_code'),
            name: (string) $request->input('name'),
            type: strtoupper((string) $request->input('type')),
            parentId: $parentId,
            isActive: (bool) $request->boolean('is_active'),
        );
    }
}
