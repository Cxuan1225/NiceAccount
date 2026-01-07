<?php

namespace App\Http\Requests\Accounting\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BalanceSheetReportRequest extends FormRequest {
    public function authorize() : bool {
        return (bool) $this->user();
    }

    public function rules() : array {
        return [
            'as_at'     => [ 'nullable', 'date' ],
            'status'    => [ 'nullable', 'string', Rule::in([ 'posted', 'draft', 'void', 'all', '' ]) ],
            'show_zero' => [ 'nullable', 'boolean' ],
        ];
    }

    public function asAt() : string {
        $asAt = $this->query('as_at');
        return ($asAt !== null && $asAt !== '') ? (string) $asAt : now()->toDateString();
    }

    public function statusRaw() : string {
        return (string) $this->query('status', 'posted');
    }

    public function showZero() : bool {
        return (bool) $this->boolean('show_zero', false);
    }
}
