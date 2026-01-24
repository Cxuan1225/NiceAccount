<?php

namespace App\Http\Requests\Security;

use Illuminate\Foundation\Http\FormRequest;

class UserIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('security.users.view');
    }

    public function rules(): array
    {
        return [
            'q' => [ 'nullable', 'string', 'max:255' ],
            'company_id' => [ 'nullable', 'integer', 'exists:companies,id' ],
            'per_page' => [ 'nullable', 'integer', 'min:1', 'max:100' ],
        ];
    }
}
