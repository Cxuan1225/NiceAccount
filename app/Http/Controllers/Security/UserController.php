<?php

namespace App\Http\Controllers\Security;

use App\DTOs\Security\UserData;
use App\DTOs\Security\UserIndexFiltersDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Security\UserIndexRequest;
use App\Http\Requests\Security\UserStoreRequest;
use App\Http\Requests\Security\UserUpdateRequest;
use App\Http\Resources\Security\UserResource;
use App\Models\Company;
use App\Models\User;
use App\Services\Security\UserService;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(UserIndexRequest $request, UserService $service)
    {
        $filters = UserIndexFiltersDTO::fromRequest($request);
        $users = $service->list($request->user(), $filters);

        return Inertia::render('Security/Users/Index', [
            'users' => UserResource::collection($users),
            'filters' => [
                'q' => $filters->q,
                'company_id' => $filters->companyId,
            ],
            'companies' => $request->user()->isSuperAdmin()
                ? Company::query()->orderBy('name')->get([ 'id', 'name' ])
                : [],
        ]);
    }

    public function create()
    {
        $user = request()->user();

        return Inertia::render('Security/Users/Create', [
            'roles' => Role::query()->orderBy('name')->get([ 'id', 'name' ]),
            'companies' => $user->isSuperAdmin()
                ? Company::query()->orderBy('name')->get([ 'id', 'name' ])
                : [],
        ]);
    }

    public function store(UserStoreRequest $request, UserService $service)
    {
        $dto = UserData::fromRequest($request);
        $service->create($request->user(), $dto);

        return redirect()->route('security.users.index');
    }

    public function edit(User $user)
    {
        $actor = request()->user();
        if (!$actor->isSuperAdmin() && (int) $user->company_id !== (int) $actor->company_id) {
            abort(403);
        }

        return Inertia::render('Security/Users/Edit', [
            'user' => UserResource::make($user),
            'roles' => Role::query()->orderBy('name')->get([ 'id', 'name' ]),
            'companies' => $actor->isSuperAdmin()
                ? Company::query()->orderBy('name')->get([ 'id', 'name' ])
                : [],
        ]);
    }

    public function update(UserUpdateRequest $request, User $user, UserService $service)
    {
        $dto = UserData::fromRequest($request);
        $service->update($request->user(), $user, $dto);

        return redirect()->route('security.users.index');
    }

    public function destroy(User $user, UserService $service)
    {
        $service->delete(request()->user(), $user);

        return redirect()->route('security.users.index');
    }
}
