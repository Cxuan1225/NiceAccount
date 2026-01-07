<?php

namespace App\Http\Requests\Accounting\OpeningBalance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpeningBalanceStoreRequest extends FormRequest {
    public function authorize() : bool {
        return true;
    }

    public function rules() : array {
        $companyId = (int) ($this->user()->company_id ?? 0);

        return [
            'entry_date'         => [ 'required', 'date' ],
            'memo'               => [ 'nullable', 'string', 'max:255' ],

            'lines'              => [ 'required', 'array', 'min:1' ],
            'lines.*.account_id' => [
                    'required',
                    'integer',
                    Rule::exists('chart_of_accounts', 'id')
                        ->where(fn ($q) => $q->where('company_id', $companyId)->where('is_active', 1)),
                ],
            // IMPORTANT: make it gt:0 so we don't accept zero rows then silently skip
            'lines.*.amount'     => [ 'required', 'numeric', 'gt:0' ],
        ];
    }
}
