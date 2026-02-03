<?php

namespace App\Http\Requests\Security;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('security.roles.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $role = $this->route('role');
        $roleId = is_object($role) && property_exists($role, 'id') ? $role->id : null;

        return [
            'name' => [ 'required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($roleId) ],
            'permissions' => [ 'nullable', 'array' ],
            'permissions.*' => [ 'string', 'max:255' ],
        ];
    }
}
