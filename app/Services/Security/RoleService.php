<?php

namespace App\Services\Security;

use App\DTOs\Security\RoleData;
use App\DTOs\Security\RoleIndexFiltersDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    /**
     * @return LengthAwarePaginator<int, Role>
     */
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

    /**
     * @return array<int, array{id:int, name:string, label:string, category:string, description:mixed}>
     */
    public function permissionsList(): array
    {
        return Permission::query()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([ 'id', 'name', 'label', 'category', 'description' ])
            ->map(function (Permission $p): array {
                $labelRaw = $p->getAttribute('label');
                $categoryRaw = $p->getAttribute('category');
                $description = $p->getAttribute('description');
                $label = is_string($labelRaw) ? $labelRaw : null;
                $category = is_string($categoryRaw) ? $categoryRaw : null;

                return [
                    'id' => (int) $p->id,
                    'name' => (string) $p->name,
                    'label' => $label ?? (string) $p->name,
                    'category' => $category ?? 'General',
                    'description' => $description,
                ];
            })
            ->all();
    }

    public function create(RoleData $dto): Role
    {
        /** @var Role $role */
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
