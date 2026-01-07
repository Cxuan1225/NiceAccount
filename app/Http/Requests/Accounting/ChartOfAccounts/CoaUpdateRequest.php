<?php

namespace App\Http\Requests\Accounting\ChartOfAccounts;

use App\Models\Accounting\ChartOfAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CoaUpdateRequest extends FormRequest {
    public function authorize() : bool { return true; }

    public function rules() : array {
        $companyId = (int) ($this->user()->company_id ?? 1);

        /** @var ChartOfAccount $account */
        $account = $this->route('account'); // route model binding name

        $coaTypes = [ 'ASSET', 'LIABILITY', 'EQUITY', 'INCOME', 'EXPENSE' ];

        return [
            'account_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('chart_of_accounts', 'account_code')
                    ->ignore($account->id)
                    ->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'name'         => [ 'required', 'string', 'max:255' ],
            'type'         => [ 'required', Rule::in($coaTypes) ],
            'parent_id'    => [
                'nullable',
                'integer',
                Rule::exists('chart_of_accounts', 'id')
                    ->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'is_active'    => [ 'required', 'boolean' ],
        ];
    }
}
