<?php

namespace App\Services\Security;

use App\DTOs\Security\RoleData;
use App\DTOs\Security\RoleIndexFiltersDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function list(RoleIndexFiltersDTO $filters): LengthAwarePaginator
    {
        return Role::query()
            ->with('permissions')
            ->when($filters->q !== '', function ($query) use ($filters) {
                $query->where('name', 'like', "%{$filters->q}%");
            })
            ->orderBy('name')
            ->paginate($filters->perPage)
            ->withQueryString();
    }

    public function permissionsList(): array
    {
        return Permission::query()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([ 'id', 'name', 'label', 'category', 'description' ])
            ->map(fn ($p) => [
                'id' => (int) $p->id,
                'name' => (string) $p->name,
                'label' => $p->label ?? $p->name,
                'category' => $p->category ?? 'General',
                'description' => $p->description,
            ])
            ->all();
    }

    public function create(RoleData $dto): Role
    {
        $role = Role::create([
            'name' => $dto->name,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($dto->permissions);

        return $role;
    }

    public function update(Role $role, RoleData $dto): Role
    {
        $role->update([
            'name' => $dto->name,
        ]);

        $role->syncPermissions($dto->permissions);

        return $role;
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }
}
