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
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class CompanyController extends Controller
{
    public function index(CompanyIndexRequest $request, CompanyService $service): Response|InertiaResponse
    {
        $filters = CompanyIndexFiltersDTO::fromRequest($request);
        $actor = $request->user();
        if (!$actor) {
            abort(403);
        }

        $companies = $service->list($actor, $filters);

        return Inertia::render('Company/Companies/Index', [
            'companies' => CompanyResource::collection($companies),
            'filters' => [
                'q' => $filters->q,
            ],
        ]);
    }

    public function create(): Response|InertiaResponse
    {
        return Inertia::render('Company/Companies/Create');
    }

    public function store(CompanyStoreRequest $request, CompanyService $service): Response|InertiaResponse
    {
        $actor = $request->user();
        if (!$actor) {
            abort(403);
        }

        $dto = CompanyData::fromRequest($request);
        $service->create($actor, $dto);

        return redirect()->route('companies.index');
    }

    public function edit(Company $company): Response|InertiaResponse
    {
        $user = request()->user();
        if ($user && !$user->isSuperAdmin() && (int) $user->company_id !== (int) $company->id) {
            abort(403);
        }

        return Inertia::render('Company/Companies/Edit', [
            'company' => CompanyResource::make($company),
        ]);
    }

    public function update(CompanyUpdateRequest $request, Company $company, CompanyService $service): Response|InertiaResponse
    {
        $actor = $request->user();
        if (!$actor) {
            abort(403);
        }

        $dto = CompanyData::fromRequest($request);
        $service->update($actor, $company, $dto);

        return redirect()->route('companies.index');
    }

    public function destroy(Company $company, CompanyService $service): Response|InertiaResponse
    {
        $actor = request()->user();
        if (!$actor) {
            abort(403);
        }

        $service->delete($actor, $company);

        return redirect()->route('companies.index');
    }
}
