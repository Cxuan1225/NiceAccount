<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class CompanySettingsController extends Controller {
    public function edit(Request $request): Response|InertiaResponse {
        $companyId = $this->currentCompanyId();

        $company = Company::query()
            ->where('id', $companyId)
            ->firstOrFail([
                'id',
                'code',
                'name',
                'base_currency',
                'timezone',
                'date_format',
                'fy_start_month',
                'email',
                'phone',
                'address_line1',
                'address_line2',
                'address_line3',
                'city',
                'state',
                'postcode',
                'country',
            ]);

        return Inertia::render('Company/Settings', [
            'company' => $company,
        ]);
    }

    public function update(Request $request): Response|InertiaResponse {
        $companyId = $this->currentCompanyId();

        /** @var array<string, mixed> $validated */
        $validated = $request->validate([
            'name'           => [ 'required', 'string', 'max:255' ],
            'base_currency'  => [ 'required', 'string', 'max:10' ],
            'timezone'       => [ 'required', 'string', 'max:64' ],
            'date_format'    => [ 'required', 'string', 'max:20' ],
            'fy_start_month' => [ 'required', 'integer', 'min:1', 'max:12' ],

            'email'          => [ 'nullable', 'email', 'max:255' ],
            'phone'          => [ 'nullable', 'string', 'max:30' ],

            'address_line1'  => [ 'nullable', 'string', 'max:255' ],
            'address_line2'  => [ 'nullable', 'string', 'max:255' ],
            'address_line3'  => [ 'nullable', 'string', 'max:255' ],
            'city'           => [ 'nullable', 'string', 'max:80' ],
            'state'          => [ 'nullable', 'string', 'max:80' ],
            'postcode'       => [ 'nullable', 'string', 'max:20' ],
            'country'        => [ 'nullable', 'string', 'max:2' ],
        ]);

        Company::query()
            ->where('id', $companyId)
            ->update($validated);

        return redirect()->route('company.settings.edit');
    }
}
