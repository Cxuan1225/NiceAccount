<?php

namespace App\Http\Requests\Accounting\JournalEntries;

use Illuminate\Foundation\Http\FormRequest;

class JournalEntryReverseRequest extends FormRequest {
    public function authorize() : bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules() : array {
        return [
            // optional, for user control:
            'entry_date' => [ 'nullable', 'date' ], // default: today
            'memo'       => [ 'nullable', 'string', 'max:255' ],
        ];
    }
}
