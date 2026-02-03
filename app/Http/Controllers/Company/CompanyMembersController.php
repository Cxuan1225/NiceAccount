<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class CompanyMembersController extends Controller {
    public function index(Request $request): Response|InertiaResponse {
        $companyId = $this->currentCompanyId();

        $company = Company::query()
            ->where('id', $companyId)
            ->firstOrFail([ 'id', 'name' ]);

        $members = $company->users()
            ->select([ 'users.id', 'users.name', 'users.email' ])
            ->orderBy('users.name')
            ->get()
            ->map(function ($u) {
                $status = data_get($u, 'pivot.status');
                $isDefault = data_get($u, 'pivot.is_default');
                $joinedAt = data_get($u, 'pivot.joined_at');
                $statusText = is_string($status) ? $status : '';

                $name = is_string($u->name ?? null) ? $u->name : '';
                $email = is_string($u->email ?? null) ? $u->email : '';

                return [
                    'id'         => (int) $u->id,
                    'name'       => $name,
                    'email'      => $email,
                    'status'     => $statusText,
                    'is_default' => (bool) ($isDefault ?? false),
                    'joined_at'  => $joinedAt,
                ];
            });

        return Inertia::render('Company/Members', [
            'company'         => $company,
            'members'         => $members,
            'activeCompanyId' => (int) $companyId,
        ]);
    }
}
