<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureActiveCompany {
    public function handle(Request $request, Closure $next) {
        $user = $request->user();
        if (!$user) return $next($request);

        // allow access to company switch routes always
        if ($request->routeIs('companies.*') || $request->is('companies', 'companies/*')) {
            return $next($request);
        }

        // no selection -> go select
        if (!$user->active_company_id) {
            $companyId = $user->companies()
                ->wherePivot('status', 'active')
                ->orderByDesc('company_user.is_default')
                ->orderBy('companies.id')
                ->value('companies.id');

            if (!$companyId) {
                return redirect()->route('companies.create');
            }

            $user->forceFill([ 'active_company_id' => (int) $companyId ])->save();
            return $next($request);
        }

        // selection exists, but confirm membership is active
        $ok = $user->companies()
            ->where('companies.id', (int) $user->active_company_id)
            ->wherePivot('status', 'active')
            ->exists();

        if (!$ok) {
            $user->forceFill([ 'active_company_id' => null ])->save();
            return redirect()->route('companies.index');
        }

        return $next($request);
    }
}
