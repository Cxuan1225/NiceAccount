<?php

namespace App\Services\Accounting\JournalEntries;

use App\DTO\Accounting\JournalEntries\JournalEntryIndexFiltersDTO;
use App\DTO\Accounting\JournalEntries\JournalEntryStoreDTO;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use App\Services\Accounting\PostingPeriods\PostingPeriodService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalEntryService {
    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function list(JournalEntryIndexFiltersDTO $dto) : LengthAwarePaginator {
        return JournalEntry::query()
            ->where('company_id', $dto->companyId)
            ->when($dto->q !== '', function ($b) use ($dto) {
                $q = $dto->q;
                $b->where(function ($sub) use ($q) {
                    $sub->where('reference_no', 'like', "%{$q}%")
                        ->orWhere('memo', 'like', "%{$q}%")
                        ->orWhere('source_type', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($dto->perPage)
            ->withQueryString()
            ->through(function (JournalEntry $e) {
                $entryDate = $e->entry_date->format('d-m-Y');
                $createdAt = $e->created_at?->format('d-m-Y H:i:s') ?? '';

                /** @var array<string, mixed> $row */
                $row = [
                    'id'           => (int) $e->id,
                    'entry_date'   => $entryDate,
                    'reference_no' => $e->reference_no,
                    'memo'         => $e->memo,
                    'status'       => $e->status,
                    'source_type'  => $e->source_type,
                    'created_at'   => $createdAt,
                ];

                return $row;
            });
    }

    /**
     * @return Collection<int, array{id:int, account_code:string, name:string, type:string}>
     */
    public function activeAccountsOptions(int $companyId): Collection {
        return ChartOfAccount::query()
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->orderBy('account_code')
            ->get([ 'id', 'account_code', 'name', 'type' ])
            ->map(fn ($a) => [
                'id'           => (int) $a->id,
                'account_code' => (string) $a->account_code,
                'name'         => (string) $a->name,
                'type'         => (string) $a->type,
            ]);
    }

    /**
     * Create a manual journal entry and post it immediately.
     */
    public function createManualPosted(JournalEntryStoreDTO $dto) : JournalEntry {
        $this->validateBusinessRules($dto);

        return DB::transaction(function () use ($dto) {
            PostingPeriodService::assertCanPost($dto->companyId, Carbon::parse($dto->entryDate));

            $je = JournalEntry::create([
                'company_id'   => $dto->companyId,
                'entry_date'   => $dto->entryDate,
                'reference_no' => $dto->referenceNo,
                'memo'         => $dto->memo,
                'source_type'  => 'manual',
                'source_id'    => null,
                'status'       => 'POSTED',
            ]);

            foreach ($dto->lines as $line) {
                JournalEntryLine::create($line->toCreateArray($dto->companyId, (int) $je->id));
            }

            return $je;
        });
    }

    private function validateBusinessRules(JournalEntryStoreDTO $dto) : void {
        if (count($dto->lines) < 2) {
            throw ValidationException::withMessages([
                'lines' => 'At least 2 lines are required.',
            ]);
        }

        $debitTotal  = 0.0;
        $creditTotal = 0.0;

        foreach ($dto->lines as $i => $l) {
            $debit  = round($l->debit, 2);
            $credit = round($l->credit, 2);

            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages([
                    "lines.$i.debit"  => 'A line cannot have both debit and credit.',
                    "lines.$i.credit" => 'A line cannot have both debit and credit.',
                ]);
            }

            if ($debit <= 0 && $credit <= 0) {
                throw ValidationException::withMessages([
                    "lines.$i.debit" => 'Enter debit or credit amount.',
                ]);
            }

            $debitTotal  += $debit;
            $creditTotal += $credit;
        }

        $debitTotal  = round($debitTotal, 2);
        $creditTotal = round($creditTotal, 2);

        if ($debitTotal !== $creditTotal) {
            throw ValidationException::withMessages([
                'lines' => "Journal entry is not balanced. Debit {$debitTotal} must equal Credit {$creditTotal}.",
            ]);
        }
    }
}
