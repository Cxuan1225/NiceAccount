<?php

namespace App\Http\Requests\Accounting\PostingPeriods;

use Illuminate\Foundation\Http\FormRequest;

class FinancialYearStoreRequest extends FormRequest {
    public function authorize() : bool {
        return true;
    }

    public function rules() : array {
        return [
            'name'       => [ 'required', 'string', 'max:50' ],
            'start_date' => [ 'required', 'date' ],
            'end_date'   => [ 'required', 'date' ],
        ];
    }
}
