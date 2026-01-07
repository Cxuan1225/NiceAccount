<?php

namespace App\Http\Requests\Accounting\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GeneralLedgerReportRequest extends FormRequest {
    public function authorize() : bool {
        return (bool) $this->user();
    }

    public function rules() : array {
        return [
            // optional: allow empty to show "select account" state
            'account_id' => [ 'nullable', 'integer', 'min:1' ],

            'from'       => [ 'nullable', 'date' ],
            'to'         => [ 'nullable', 'date' ],

            'status'     => [ 'nullable', 'string', Rule::in([ 'posted', 'draft', 'void', 'all', '' ]) ],
            'show_zero'  => [ 'nullable', 'boolean' ],
        ];
    }

    public function withValidator($validator) : void {
        $validator->after(function ($v) {
            $from = $this->query('from');
            $to   = $this->query('to');

            if ($from && $to && strtotime($from) > strtotime($to)) {
                $v->errors()->add('from', 'From date cannot be later than To date.');
            }
        });
    }

    public function accountId() : ?int {
        $v = $this->query('account_id');
        if ($v === null || $v === '') return null;
        return (int) $v;
    }

    public function fromDate() : ?string {
        $v = $this->query('from');
        return ($v !== null && $v !== '') ? (string) $v : null;
    }

    public function toDate() : ?string {
        $v = $this->query('to');
        return ($v !== null && $v !== '') ? (string) $v : null;
    }

    public function statusRaw() : string {
        return (string) $this->query('status', 'posted');
    }

    public function showZero() : bool {
        return (bool) $this->boolean('show_zero', false);
    }
}
