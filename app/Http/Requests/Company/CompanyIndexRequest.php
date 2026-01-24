<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class CompanyIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('company.companies.view');
    }

    public function rules(): array
    {
        return [
            'q' => [ 'nullable', 'string', 'max:255' ],
            'per_page' => [ 'nullable', 'integer', 'min:1', 'max:100' ],
        ];
    }
}
