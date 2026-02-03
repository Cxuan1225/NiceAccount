<?php

namespace App\Http\Requests\Accounting\JournalEntries;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class JournalEntryStoreRequest extends FormRequest {
    public function authorize() : bool { return true; }

    /**
     * @return array<string, mixed>
     */
    public function rules() : array {
        $user = $this->user();
        $companyId = (int) ($user?->isSuperAdmin()
            ? ($user->active_company_id ?? $user->company_id ?? 0)
            : ($user->company_id ?? 0));

        return [
            'entry_date'          => [ 'required', 'date' ],
            'reference_no'        => [ 'nullable', 'string', 'max:100' ],
            'memo'                => [ 'nullable', 'string', 'max:255' ],

            'lines'               => [ 'required', 'array', 'min:2' ],
            'lines.*.account_id'  => [
                'required',
                'integer',
                Rule::exists('chart_of_accounts', 'id')
                    ->where(fn (Builder $q) => $q->where('company_id', $companyId)),
            ],
            'lines.*.description' => [ 'nullable', 'string', 'max:255' ],
            'lines.*.debit'       => [ 'nullable', 'numeric', 'min:0' ],
            'lines.*.credit'      => [ 'nullable', 'numeric', 'min:0' ],
        ];
    }
}
