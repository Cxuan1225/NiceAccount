<?php

namespace App\Http\Requests\Security;

use Illuminate\Foundation\Http\FormRequest;

class RoleIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('security.roles.view');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => [ 'nullable', 'string', 'max:255' ],
            'per_page' => [ 'nullable', 'integer', 'min:1', 'max:100' ],
        ];
    }
}
