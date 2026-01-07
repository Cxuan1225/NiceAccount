<?php

namespace App\Http\Requests\Accounting\JournalEntries;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JournalEntryStoreRequest extends FormRequest {
    public function authorize() : bool { return true; }

    public function rules() : array {
        $companyId = (int) ($this->user()->company_id ?? 1);

        return [
            'entry_date'          => [ 'required', 'date' ],
            'reference_no'        => [ 'nullable', 'string', 'max:100' ],
            'memo'                => [ 'nullable', 'string', 'max:255' ],

            'lines'               => [ 'required', 'array', 'min:2' ],
            'lines.*.account_id'  => [
                'required',
                'integer',
                Rule::exists('chart_of_accounts', 'id')
                    ->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'lines.*.description' => [ 'nullable', 'string', 'max:255' ],
            'lines.*.debit'       => [ 'nullable', 'numeric', 'min:0' ],
            'lines.*.credit'      => [ 'nullable', 'numeric', 'min:0' ],
        ];
    }
}
