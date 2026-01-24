<?php

namespace App\Http\Controllers;

use App\Services\Company\CompanyScopeResolver;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController {
    protected function currentCompanyId() : int {
        $user = request()->user();
        if (!$user) {
            abort(401);
        }

        $resolver = app(CompanyScopeResolver::class);
        $requested = $user->active_company_id ?: $user->company_id;
        $companyId = $resolver->resolveCompanyId($user, $requested ? (int) $requested : null);

        if ($companyId && (int) $user->active_company_id !== (int) $companyId) {
            $user->forceFill([ 'active_company_id' => (int) $companyId ])->save();
        }

        return (int) $companyId;
    }
}
