<?php

namespace App\Http\Requests\Security;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('security.users.create');
    }

    public function rules(): array
    {
        return [
            'name' => [ 'required', 'string', 'max:255' ],
            'email' => [ 'required', 'string', 'email', 'max:255', 'unique:users,email' ],
            'password' => [ 'required', 'string', 'min:8' ],
            'company_id' => [ 'nullable', 'integer', 'exists:companies,id' ],
            'roles' => [ 'nullable', 'array' ],
            'roles.*' => [ 'string', 'max:255' ],
        ];
    }
}
