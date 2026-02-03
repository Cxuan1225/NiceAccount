<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class CompanyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('company.companies.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [ 'required', 'string', 'max:255' ],
            'base_currency' => [ 'required', 'string', 'max:10' ],
            'timezone' => [ 'required', 'string', 'max:64' ],
            'date_format' => [ 'required', 'string', 'max:20' ],
            'fy_start_month' => [ 'required', 'integer', 'min:1', 'max:12' ],
            'email' => [ 'nullable', 'email', 'max:255' ],
            'phone' => [ 'nullable', 'string', 'max:30' ],
            'address_line1' => [ 'nullable', 'string', 'max:255' ],
            'address_line2' => [ 'nullable', 'string', 'max:255' ],
            'address_line3' => [ 'nullable', 'string', 'max:255' ],
            'city' => [ 'nullable', 'string', 'max:80' ],
            'state' => [ 'nullable', 'string', 'max:80' ],
            'postcode' => [ 'nullable', 'string', 'max:20' ],
            'country' => [ 'nullable', 'string', 'max:2' ],
        ];
    }
}
