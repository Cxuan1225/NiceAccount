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
        $parentIdRaw = $request->input('parent_id');
        $parentId = null;
        if ($parentIdRaw !== null && $parentIdRaw !== '' && is_numeric($parentIdRaw)) {
            $parentId = (int) $parentIdRaw;
        }
        $accountCode = $request->input('account_code');
        $name = $request->input('name');
        $type = $request->input('type');

        return new self(
            companyId: $companyId,
            accountCode: is_string($accountCode) ? $accountCode : '',
            name: is_string($name) ? $name : '',
            type: strtoupper(is_string($type) ? $type : ''),
            parentId: $parentId,
            isActive: (bool) $request->boolean('is_active'),
        );
    }
}
