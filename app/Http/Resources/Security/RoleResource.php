<?php

namespace App\Http\Resources\Security;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'permissions' => $this->permissions
                ? $this->permissions->pluck('name')->values()->all()
                : [],
        ];
    }
}
