<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class BaseAccountingController extends Controller {
    protected int $companyId;

    public function __construct() {
        $this->middleware([ 'auth', 'verified' ]);

        $this->middleware(function (Request $request, \Closure $next) {
            $this->companyId = $this->currentCompanyId();
            return $next($request);
        });
    }

    /**
     * @return array<int, string>
     */
    protected function coaTypes() : array {
        return [ 'ASSET', 'LIABILITY', 'EQUITY', 'INCOME', 'EXPENSE' ];
    }


    protected function normalizeStatus(?string $status, string $default = 'POSTED') : string {
        return strtoupper(trim($status ?: $default));
    }

    /**
     * @return array{from:string|null, to:string|null}
     */
    protected function dateRange(Request $request) : array {
        return [
            'from' => $request->query('from'),
            'to'   => $request->query('to'),
        ];
    }
}
