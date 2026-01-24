<?php

namespace App\Http\Controllers\Company;

use App\DTOs\Company\CompanyData;
use App\DTOs\Company\CompanyIndexFiltersDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CompanyIndexRequest;
use App\Http\Requests\Company\CompanyStoreRequest;
use App\Http\Requests\Company\CompanyUpdateRequest;
use App\Http\Resources\Company\CompanyResource;
use App\Models\Company;
use App\Services\Company\CompanyService;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index(CompanyIndexRequest $request, CompanyService $service)
    {
        $filters = CompanyIndexFiltersDTO::fromRequest($request);
        $companies = $service->list($request->user(), $filters);

        return Inertia::render('Company/Companies/Index', [
            'companies' => CompanyResource::collection($companies),
            'filters' => [
                'q' => $filters->q,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Company/Companies/Create');
    }

    public function store(CompanyStoreRequest $request, CompanyService $service)
    {
        $dto = CompanyData::fromRequest($request);
        $service->create($request->user(), $dto);

        return redirect()->route('companies.index');
    }

    public function edit(Company $company)
    {
        $user = request()->user();
        if ($user && !$user->isSuperAdmin() && (int) $user->company_id !== (int) $company->id) {
            abort(403);
        }

        return Inertia::render('Company/Companies/Edit', [
            'company' => CompanyResource::make($company),
        ]);
    }

    public function update(CompanyUpdateRequest $request, Company $company, CompanyService $service)
    {
        $dto = CompanyData::fromRequest($request);
        $service->update($request->user(), $company, $dto);

        return redirect()->route('companies.index');
    }

    public function destroy(Company $company, CompanyService $service)
    {
        $service->delete(request()->user(), $company);

        return redirect()->route('companies.index');
    }
}
