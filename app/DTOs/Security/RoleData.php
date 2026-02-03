<?php

namespace App\DTOs\Security;

use Illuminate\Http\Request;

class RoleData
{
    /**
     * @param array<int, string> $permissions
     */
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

        $nameRaw = $request->input('name');
        $name = is_string($nameRaw) ? $nameRaw : '';

        return new self(
            name: $name,
            permissions: array_values(array_filter($permissions, 'is_string')),
        );
    }
}
