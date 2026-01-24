<?php

namespace App\DTOs\Security;

use Illuminate\Http\Request;

class UserData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $password,
        public readonly ?int $companyId,
        public readonly array $roleNames,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $roleNames = $request->input('roles', []);
        if (!is_array($roleNames)) {
            $roleNames = [];
        }

        $companyId = $request->input('company_id');
        $companyId = $companyId === null || $companyId === '' ? null : (int) $companyId;

        return new self(
            name: (string) $request->input('name'),
            email: (string) $request->input('email'),
            password: $request->input('password'),
            companyId: $companyId,
            roleNames: $roleNames,
        );
    }
}
