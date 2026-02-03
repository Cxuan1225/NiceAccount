<?php

namespace App\Http\Requests\Security;

use Illuminate\Foundation\Http\FormRequest;

class RoleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('security.roles.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [ 'required', 'string', 'max:255', 'unique:roles,name' ],
            'permissions' => [ 'nullable', 'array' ],
            'permissions.*' => [ 'string', 'max:255' ],
        ];
    }
}
