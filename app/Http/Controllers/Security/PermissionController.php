<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\Security\PermissionIndexRequest;
use App\Http\Resources\Security\PermissionResource;
use App\Services\Security\PermissionService;
use Inertia\Inertia;

class PermissionController extends Controller
{
    public function index(PermissionIndexRequest $request, PermissionService $service)
    {
        $permissions = $service->list(
            (string) $request->query('q', ''),
            (int) $request->query('per_page', 20),
        );

        return Inertia::render('Security/Permissions/Index', [
            'permissions' => PermissionResource::collection($permissions),
            'filters' => [
                'q' => (string) $request->query('q', ''),
            ],
        ]);
    }
}
