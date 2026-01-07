<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyMembersController extends Controller {
    public function index(Request $request) {
        $companyId = $this->currentCompanyId();

        $company = Company::query()
            ->where('id', $companyId)
            ->firstOrFail([ 'id', 'name' ]);

        $members = $company->users()
            ->select([ 'users.id', 'users.name', 'users.email' ])
            ->orderBy('users.name')
            ->get()
            ->map(fn ($u) => [
                'id'         => (int) $u->id,
                'name'       => (string) $u->name,
                'email'      => (string) $u->email,
                'status'     => (string) ($u->pivot->status ?? ''),
                'is_default' => (bool) ($u->pivot->is_default ?? false),
                'joined_at'  => $u->pivot->joined_at,
            ]);

        return Inertia::render('Company/Members', [
            'company'         => $company,
            'members'         => $members,
            'activeCompanyId' => (int) $companyId,
        ]);
    }
}
