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
use Inertia\Response as InertiaResponse;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function index(UserIndexRequest $request, UserService $service): Response|InertiaResponse
    {
        $filters = UserIndexFiltersDTO::fromRequest($request);
        $actor = $request->user();
        if (!$actor) {
            abort(403);
        }
        $users = $service->list($actor, $filters);

        return Inertia::render('Security/Users/Index', [
            'users' => UserResource::collection($users),
            'filters' => [
                'q' => $filters->q,
                'company_id' => $filters->companyId,
            ],
            'companies' => $actor->isSuperAdmin()
                ? Company::query()->orderBy('name')->get([ 'id', 'name' ])
                : [],
        ]);
    }

    public function create(): Response|InertiaResponse
    {
        $user = request()->user();
        if (!$user) {
            abort(403);
        }

        return Inertia::render('Security/Users/Create', [
            'roles' => Role::query()->orderBy('name')->get([ 'id', 'name' ]),
            'companies' => $user->isSuperAdmin()
                ? Company::query()->orderBy('name')->get([ 'id', 'name' ])
                : [],
        ]);
    }

    public function store(UserStoreRequest $request, UserService $service): Response|InertiaResponse
    {
        $dto = UserData::fromRequest($request);
        $actor = $request->user();
        if (!$actor) {
            abort(403);
        }
        $service->create($actor, $dto);

        return redirect()->route('security.users.index');
    }

    public function edit(User $user): Response|InertiaResponse
    {
        $actor = request()->user();
        if (!$actor) {
            abort(403);
        }
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

    public function update(UserUpdateRequest $request, User $user, UserService $service): Response|InertiaResponse
    {
        $dto = UserData::fromRequest($request);
        $actor = $request->user();
        if (!$actor) {
            abort(403);
        }
        $service->update($actor, $user, $dto);

        return redirect()->route('security.users.index');
    }

    public function destroy(User $user, UserService $service): Response|InertiaResponse
    {
        $actor = request()->user();
        if (!$actor) {
            abort(403);
        }
        $service->delete($actor, $user);

        return redirect()->route('security.users.index');
    }
}
