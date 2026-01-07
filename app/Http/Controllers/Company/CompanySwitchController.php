<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanySwitchController extends Controller {
    public function index(Request $request) {
        $user = $request->user();

        $companies = $user->companies()
            ->wherePivot('status', 'active')
            ->orderBy('companies.name')
            ->get([
                'companies.id',
                'companies.code',
                'companies.name',
                'companies.base_currency',
            ]);

        return Inertia::render('Company/Switch', [
            'companies'       => $companies,
            'activeCompanyId' => (int) ($user->active_company_id ?? 0),
        ]);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'company_id' => [ 'required', 'integer' ],
        ]);

        $user      = $request->user();
        $companyId = (int) $validated['company_id'];

        $ok = $user->companies()
            ->where('companies.id', $companyId)
            ->wherePivot('status', 'active')
            ->exists();

        if (!$ok) {
            abort(403);
        }

        $user->forceFill([
            'active_company_id' => $companyId,
        ])->save();

        return redirect()->back();
    }
}
