<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class OpeningBalanceController extends BaseAccountingController
{
    public function create()
    {

        $accounts = ChartOfAccount::query()
            ->where('company_id', $this->companyId)
            ->where('is_active', 1)
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'name', 'type']);

        // Find Opening Balance Equity account (recommended)
        $obe = ChartOfAccount::query()
            ->where('company_id', $this->companyId)
            ->where('account_code', '3200')
            ->first(['id', 'account_code', 'name', 'type']);

        return Inertia::render('Accountings/OpeningBalance/Create', [
            'accounts' => $accounts,
            'openingBalanceEquity' => $obe,
        ]);
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'memo' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', Rule::exists('chart_of_accounts', 'id')->where(fn($q) => $q->where('company_id', $this->companyId))],
            'lines.*.amount' => ['required', 'numeric', 'min:0'],
        ]);

        // Load accounts to know type
        $accountIds = collect($validated['lines'])->pluck('account_id')->unique()->values();
        $accounts = ChartOfAccount::query()
            ->where('company_id', $this->companyId)
            ->whereIn('id', $accountIds)
            ->get(['id', 'type', 'name', 'account_code'])
            ->keyBy('id');

        // Find Opening Balance Equity (3200). If not exist, fallback to 3000.
        $obe = ChartOfAccount::query()
            ->where('company_id', $this->companyId)
            ->where('account_code', '3200')
            ->first();

        if (!$obe) {
            $obe = ChartOfAccount::query()
                ->where('company_id', $this->companyId)
                ->where('account_code', '3000')
                ->first();
        }

        if (!$obe) {
            return back()->withErrors(['opening_balance_equity' => 'Opening Balance Equity account not found (3200/3000).']);
        }

        // Build JE lines from amounts
        $debitTotal = 0.0;
        $creditTotal = 0.0;

        $jeLines = [];

        foreach ($validated['lines'] as $line) {
            $amount = (float) $line['amount'];
            if ($amount <= 0)
                continue;

            $acc = $accounts[$line['account_id']] ?? null;
            if (!$acc)
                continue;

            // Rule:
            // ASSET => Debit
            // LIABILITY/EQUITY/INCOME => Credit (for opening, income normally zero but keep rule consistent)
            // EXPENSE => Debit (rare in opening, but ok)
            $debit = 0.0;
            $credit = 0.0;

            if (in_array($acc->type, ['ASSET', 'EXPENSE'], true)) {
                $debit = $amount;
                $debitTotal += $amount;
            } else {
                $credit = $amount;
                $creditTotal += $amount;
            }

            $jeLines[] = [
                'company_id' => $this->companyId,
                'account_id' => $acc->id,
                'debit' => $debit,
                'credit' => $credit,
                'description' => 'Opening Balance - ' . $acc->name,
            ];
        }

        if (count($jeLines) === 0) {
            return back()->withErrors(['lines' => 'Please enter at least one amount.']);
        }

        // Auto-balance using Opening Balance Equity
        $diff = round($debitTotal - $creditTotal, 2);

        if ($diff > 0) {
            // Need extra credit
            $jeLines[] = [
                'company_id' => $this->companyId,
                'account_id' => $obe->id,
                'debit' => 0,
                'credit' => $diff,
                'description' => 'Opening Balance Equity (auto)',
            ];
            $creditTotal += $diff;
        } elseif ($diff < 0) {
            // Need extra debit
            $need = abs($diff);
            $jeLines[] = [
                'company_id' => $this->companyId,
                'account_id' => $obe->id,
                'debit' => $need,
                'credit' => 0,
                'description' => 'Opening Balance Equity (auto)',
            ];
            $debitTotal += $need;
        }

        // Final check
        if (round($debitTotal, 2) !== round($creditTotal, 2)) {
            return back()->withErrors(['lines' => 'Opening balance is not balanced. Please review your amounts.']);
        }
        $companyId = $this->companyId;
        DB::transaction(function () use ($companyId, $validated, $jeLines) {
            $je = JournalEntry::create([
                'company_id' => $companyId,
                'entry_date' => $validated['entry_date'],
                'reference_no' => 'OPENING',
                'memo' => $validated['memo'] ?? 'Opening Balance',
                'source_type' => 'opening_balance',
                'source_id' => null,
                'status' => 'POSTED',
            ]);

            foreach ($jeLines as $l) {
                $l['journal_entry_id'] = $je->id;
                JournalEntryLine::create($l);
            }
        });

        return redirect()->route('coa.index');
    }
}
