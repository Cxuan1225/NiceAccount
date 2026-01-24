<?php

namespace App\DTOs\Security;

use Illuminate\Http\Request;

class RoleData
{
    public function __construct(
        public readonly string $name,
        public readonly array $permissions,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $permissions = $request->input('permissions', []);
        if (!is_array($permissions)) {
            $permissions = [];
        }

        return new self(
            name: (string) $request->input('name'),
            permissions: $permissions,
        );
    }
}
