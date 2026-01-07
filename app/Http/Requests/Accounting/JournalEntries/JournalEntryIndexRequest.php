<?php

namespace App\Http\Requests\Accounting\JournalEntries;

use Illuminate\Foundation\Http\FormRequest;

class JournalEntryIndexRequest extends FormRequest {
    public function authorize() : bool { return true; }

    public function rules() : array {
        return [
            'q'        => [ 'nullable', 'string', 'max:255' ],
            'per_page' => [ 'nullable', 'integer', 'min:1', 'max:200' ],
        ];
    }
}
