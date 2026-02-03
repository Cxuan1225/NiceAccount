<?php

namespace App\Services\Security;

use App\DTOs\Security\UserData;
use App\DTOs\Security\UserIndexFiltersDTO;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function list(User $actor, UserIndexFiltersDTO $filters): LengthAwarePaginator
    {
        return User::query()
            ->with('company')
            ->when(!$actor->isSuperAdmin(), function ($query) use ($actor) {
                $query->where('company_id', (int) $actor->company_id);
            })
            ->when($actor->isSuperAdmin() && $filters->companyId, function ($query) use ($filters) {
                $query->where('company_id', $filters->companyId);
            })
            ->when($filters->q !== '', function ($query) use ($filters) {
                $query->where(function ($sub) use ($filters) {
                    $sub->where('name', 'like', "%{$filters->q}%")
                        ->orWhere('email', 'like', "%{$filters->q}%");
                });
            })
            ->orderBy('name')
            ->paginate($filters->perPage)
            ->withQueryString();
    }

    public function create(User $actor, UserData $dto): User
    {
        $companyId = $actor->isSuperAdmin()
            ? ($dto->companyId ?? $actor->company_id)
            : $actor->company_id;

        if (!$companyId) {
            abort(422, 'Company is required.');
        }

        $user = User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make((string) $dto->password),
            'company_id' => (int) $companyId,
            'active_company_id' => (int) $companyId,
            'email_verified_at' => now(),
        ]);

        if (!empty($dto->roleNames)) {
            $user->syncRoles($dto->roleNames);
        }

        return $user;
    }

    public function update(User $actor, User $user, UserData $dto): User
    {
        if (!$actor->isSuperAdmin() && (int) $user->company_id !== (int) $actor->company_id) {
            abort(403);
        }

        $companyId = $actor->isSuperAdmin()
            ? ($dto->companyId ?? $user->company_id)
            : $actor->company_id;

        $user->update([
            'name' => $dto->name,
            'email' => $dto->email,
            'company_id' => $companyId,
            'active_company_id' => $companyId,
            'password' => $dto->password ? Hash::make((string) $dto->password) : $user->password,
        ]);

        $user->syncRoles($dto->roleNames);

        return $user;
    }

    public function delete(User $actor, User $user): void
    {
        if ($actor->id === $user->id) {
            abort(422, 'Cannot delete your own account.');
        }

        if (!$actor->isSuperAdmin() && (int) $user->company_id !== (int) $actor->company_id) {
            abort(403);
        }

        $user->delete();
    }
}
