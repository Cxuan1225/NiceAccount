<?php

namespace App\Http\Requests\Accounting\ChartOfAccounts;

use App\Models\Accounting\ChartOfAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CoaUpdateRequest extends FormRequest {
    public function authorize() : bool { return true; }

    /**
     * @return array<string, mixed>
     */
    public function rules() : array {
        $user = $this->user();
        $companyId = (int) ($user?->isSuperAdmin()
            ? ($user->active_company_id ?? $user->company_id ?? 0)
            : ($user->company_id ?? 0));

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
                    ->where(fn (Builder $q) => $q->where('company_id', $companyId)),
            ],
            'name'         => [ 'required', 'string', 'max:255' ],
            'type'         => [ 'required', Rule::in($coaTypes) ],
            'parent_id'    => [
                'nullable',
                'integer',
                Rule::exists('chart_of_accounts', 'id')
                    ->where(fn (Builder $q) => $q->where('company_id', $companyId)),
            ],
            'is_active'    => [ 'required', 'boolean' ],
        ];
    }
}
