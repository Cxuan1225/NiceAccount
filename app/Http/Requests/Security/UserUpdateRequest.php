<?php

namespace App\Http\Requests\Security;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('security.users.update');
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? null;

        return [
            'name' => [ 'required', 'string', 'max:255' ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [ 'nullable', 'string', 'min:8' ],
            'company_id' => [ 'nullable', 'integer', 'exists:companies,id' ],
            'roles' => [ 'nullable', 'array' ],
            'roles.*' => [ 'string', 'max:255' ],
        ];
    }
}
