<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveCompany
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        // allow access to company switch routes always
        if ($request->routeIs('companies.*') || $request->is('companies', 'companies/*')) {
            return $next($request);
        }

        // no selection -> go select
        if (!$user->active_company_id) {
            $companyId = (int) ($user->company_id ?? 0);
            if ($companyId > 0) {
                $user->forceFill(['active_company_id' => $companyId])->save();
                return $next($request);
            }

            return redirect()->route('companies.create');
        }

        // selection exists, but confirm membership is active
        $ok = $user->companies()
            ->where('companies.id', (int) $user->active_company_id)
            ->wherePivot('status', 'active')
            ->exists();

        if (!$ok) {
            $user->forceFill(['active_company_id' => null])->save();
            return redirect()->route('companies.select');
        }

        return $next($request);
    }
}
