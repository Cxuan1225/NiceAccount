<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController {
    protected function currentCompanyId() : int {
        $user = request()->user();
        if (!$user) abort(401);

        // 1) If active_company_id set, validate membership
        if ($user->active_company_id) {
            $ok = $user->companies()
                ->where('companies.id', (int) $user->active_company_id)
                ->wherePivot('status', 'active')
                ->exists();

            if ($ok) {
                return (int) $user->active_company_id;
            }

            // stale/invalid selection -> clear and fall back
            $user->forceFill([ 'active_company_id' => null ])->save();
        }

        // 2) Find default active membership, else first active membership
        $companyId = $user->companies()
            ->wherePivot('status', 'active')
            ->orderByDesc('company_user.is_default')
            ->orderBy('companies.id')
            ->value('companies.id');

        if (!$companyId) abort(403, 'No company selected.');

        // 3) Persist
        $user->forceFill([ 'active_company_id' => (int) $companyId ])->save();

        return (int) $companyId;
    }
}
