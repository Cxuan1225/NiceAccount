<?php

namespace App\Http\Requests\Accounting\ChartOfAccounts;

use Illuminate\Foundation\Http\FormRequest;

class CoaIndexRequest extends FormRequest {
    public function authorize() : bool {
        return true; // or permission check
    }

    public function rules() : array {
        return [
            'q'        => [ 'nullable', 'string', 'max:255' ],
            'type'     => [ 'nullable', 'string', 'max:20' ],
            'per_page' => [ 'nullable', 'integer', 'min:1', 'max:200' ],
        ];
    }
}
