<?php

namespace App\Http\Resources\Security;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

/**
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    /**
     * @return array{id:int, name:string, permissions:array<int, string>}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'permissions' => $this->permissions
                ->pluck('name')
                ->map(fn ($name) => is_string($name) ? $name : '')
                ->values()
                ->all(),
        ];
    }
}
