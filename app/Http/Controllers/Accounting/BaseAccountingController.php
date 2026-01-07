<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

abstract class BaseAccountingController extends Controller
{
    protected int $companyId;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->companyId = $request->user()->company_id ?? 1;
            return $next($request);
        });
    }

    protected function coaTypes(): array
    {
        return ['ASSET', 'LIABILITY', 'EQUITY', 'INCOME', 'EXPENSE'];
    }


    protected function normalizeStatus(?string $status, string $default = 'POSTED'): string
    {
        return strtoupper(trim($status ?: $default));
    }

    protected function dateRange(Request $request): array
    {
        return [
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];
    }
}

