<?php

namespace App\Http\Resources\Security;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array{
     *   id:int,
     *   name:string,
     *   email:string,
     *   company_id:int|null,
     *   company:array{id:int, name:string}|null,
     *   roles:array<int, string>
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'email' => (string) $this->email,
            'company_id' => $this->company_id ? (int) $this->company_id : null,
            'company' => $this->company
                ? [
                    'id' => (int) $this->company->id,
                    'name' => (string) $this->company->name,
                ]
                : null,
            'roles' => $this->getRoleNames()
                ->map(fn ($name) => is_string($name) ? $name : '')
                ->values()
                ->all(),
        ];
    }
}
