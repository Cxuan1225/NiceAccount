<?php

namespace App\Http\Requests\Accounting\PostingPeriods;

use Illuminate\Foundation\Http\FormRequest;

class PostingPeriodBulkRequest extends FormRequest {
    public function authorize() : bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules() : array {
        return [
            'ids'   => [ 'required', 'array', 'min:1' ],
            'ids.*' => [ 'integer' ],
        ];
    }
}
