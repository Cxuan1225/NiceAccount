<?php

namespace App\Http\Controllers\Security;

use App\DTOs\Security\RoleData;
use App\DTOs\Security\RoleIndexFiltersDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Security\RoleIndexRequest;
use App\Http\Requests\Security\RoleStoreRequest;
use App\Http\Requests\Security\RoleUpdateRequest;
use App\Http\Resources\Security\RoleResource;
use App\Services\Security\RoleService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    public function index(RoleIndexRequest $request, RoleService $service): Response|InertiaResponse
    {
        $filters = RoleIndexFiltersDTO::fromRequest($request);
        $roles = $service->list($filters);

        return Inertia::render('Security/Roles/Index', [
            'roles' => RoleResource::collection($roles),
            'filters' => [
                'q' => $filters->q,
            ],
        ]);
    }

    public function create(RoleService $service): Response|InertiaResponse
    {
        return Inertia::render('Security/Roles/Create', [
            'permissions' => $service->permissionsList(),
        ]);
    }

    public function store(RoleStoreRequest $request, RoleService $service): Response|InertiaResponse
    {
        $dto = RoleData::fromRequest($request);
        $service->create($dto);

        return redirect()->route('security.roles.index');
    }

    public function edit(Role $role, RoleService $service): Response|InertiaResponse
    {
        return Inertia::render('Security/Roles/Edit', [
            'role' => RoleResource::make($role->load('permissions')),
            'permissions' => $service->permissionsList(),
        ]);
    }

    public function update(RoleUpdateRequest $request, Role $role, RoleService $service): Response|InertiaResponse
    {
        $dto = RoleData::fromRequest($request);
        $service->update($role, $dto);

        return redirect()->route('security.roles.index');
    }

    public function destroy(Role $role, RoleService $service): Response|InertiaResponse
    {
        $service->delete($role);

        return redirect()->route('security.roles.index');
    }
}
