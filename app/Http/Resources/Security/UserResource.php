<?php

namespace App\Http\Resources\Security;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
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
            'roles' => $this->getRoleNames()->values()->all(),
        ];
    }
}
