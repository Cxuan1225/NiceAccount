<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanySwitchController extends Controller {
    public function index(Request $request) {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $companies = Company::query()
                ->orderBy('companies.name')
                ->get([
                    'companies.id',
                    'companies.code',
                    'companies.name',
                    'companies.base_currency',
                ]);
        } else {
            $companies = Company::query()
                ->where('companies.id', (int) $user->company_id)
                ->orderBy('companies.name')
                ->get([
                    'companies.id',
                    'companies.code',
                    'companies.name',
                    'companies.base_currency',
                ]);
        }

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

        $ok = $user->isSuperAdmin()
            ? Company::query()->where('id', $companyId)->exists()
            : (int) $user->company_id === $companyId;

        if (!$ok) {
            abort(403);
        }

        $user->forceFill([
            'active_company_id' => $companyId,
            'company_id'        => $user->isSuperAdmin() ? $user->company_id : $companyId,
        ])->save();

        return redirect()->back();
    }
}
