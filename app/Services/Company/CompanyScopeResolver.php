<?php

namespace App\Services\Company;

use App\Models\User;

class CompanyScopeResolver
{
    public function resolveCompanyId(User $user, ?int $requestedCompanyId = null): int
    {
        if ($user->isSuperAdmin()) {
            $fallback = (int) ($user->company_id ?? $user->active_company_id ?? 0);
            return $requestedCompanyId ?: $fallback;
        }

        $companyId = (int) ($user->company_id ?? 0);
        if ($companyId <= 0) {
            abort(403, 'No company assigned.');
        }

        if ($requestedCompanyId !== null && (int) $requestedCompanyId !== $companyId) {
            abort(403, 'Company scope mismatch.');
        }

        return $companyId;
    }
}
