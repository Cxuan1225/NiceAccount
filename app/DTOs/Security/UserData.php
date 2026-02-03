<?php

namespace App\DTOs\Security;

use Illuminate\Http\Request;

class UserData
{
    /**
     * @param array<int, string> $roleNames
     */
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

        $companyIdRaw = $request->input('company_id');
        $companyId = null;
        if ($companyIdRaw !== null && $companyIdRaw !== '' && is_numeric($companyIdRaw)) {
            $companyId = (int) $companyIdRaw;
        }
        $nameRaw = $request->input('name');
        $emailRaw = $request->input('email');
        $passwordRaw = $request->input('password');
        $name = is_string($nameRaw) ? $nameRaw : '';
        $email = is_string($emailRaw) ? $emailRaw : '';
        $password = is_string($passwordRaw) ? $passwordRaw : null;

        return new self(
            name: $name,
            email: $email,
            password: $password,
            companyId: $companyId,
            roleNames: array_values(array_filter($roleNames, 'is_string')),
        );
    }
}
