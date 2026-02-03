<?php

namespace App\Http\Requests\Accounting\PostingPeriods;

use Illuminate\Foundation\Http\FormRequest;

class PostingPeriodIndexRequest extends FormRequest {
    public function authorize() : bool {
        return true; // add policy later if needed
    }

    /**
     * @return array<string, mixed>
     */
    public function rules() : array {
        return [
            // optional filters if you want later
            'financial_year_id' => [ 'nullable', 'integer' ],
        ];
    }
}
