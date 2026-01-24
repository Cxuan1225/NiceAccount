<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyCreateController extends Controller
{
    public function create()
    {
        return Inertia::render('Company/Create', [
            'defaults' => [
                'base_currency' => 'MYR',
                'timezone' => 'Asia/Kuala_Lumpur',
                'date_format' => 'd/m/Y',
                'fy_start_month' => 1,
                'country' => 'MY',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [ 'required', 'string', 'max:255' ],
            'base_currency' => [ 'required', 'string', 'max:10' ],
            'timezone' => [ 'required', 'string', 'max:64' ],
            'date_format' => [ 'required', 'string', 'max:20' ],
            'fy_start_month' => [ 'required', 'integer', 'min:1', 'max:12' ],
        ]);

        $code = strtoupper(substr(preg_replace('/\s+/', '', $validated['name']), 0, 10));

        $company = Company::create([
            ...$validated,
            'code' => $code,
        ]);

        $user = $request->user();
        $isDefault = !$user->companies()->exists();

        $user->companies()->attach($company->id, [
            'status' => 'active',
            'is_default' => $isDefault,
            'joined_at' => now(),
        ]);

        $user->forceFill([ 'active_company_id' => $company->id ])->save();

        return redirect()->route('dashboard');
    }
}
