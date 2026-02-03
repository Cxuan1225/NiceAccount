<?php

namespace App\Http\Controllers\Accounting;

use App\DTO\Accounting\JournalEntries\JournalEntryIndexFiltersDTO;
use App\DTO\Accounting\JournalEntries\JournalEntryReverseDTO;
use App\DTO\Accounting\JournalEntries\JournalEntryStoreDTO;
use App\Http\Requests\Accounting\JournalEntries\JournalEntryIndexRequest;
use App\Http\Requests\Accounting\JournalEntries\JournalEntryReverseRequest;
use App\Http\Requests\Accounting\JournalEntries\JournalEntryStoreRequest;
use App\Models\Accounting\JournalEntry;
use App\Services\Accounting\JournalEntries\JournalEntryQueryService;
use App\Services\Accounting\JournalEntries\JournalEntryReversalService;
use App\Services\Accounting\JournalEntries\JournalEntryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class JournalEntryController extends BaseAccountingController {
    public function index(JournalEntryIndexRequest $request, JournalEntryService $service): Response|InertiaResponse {
        $filters = JournalEntryIndexFiltersDTO::fromRequest($request, $this->companyId);

        $entries = $service->list($filters);

        return Inertia::render('Accountings/JournalEntries/Index', [
            'entries' => $entries,
            'filters' => $filters->toFiltersArray(),
        ]);
    }

    public function create(JournalEntryService $service): Response|InertiaResponse {
        return Inertia::render('Accountings/JournalEntries/Create', [
            'accounts' => $service->activeAccountsOptions($this->companyId),
        ]);
    }

    public function store(JournalEntryStoreRequest $request, JournalEntryService $service): Response|InertiaResponse {
        $dto = JournalEntryStoreDTO::fromRequest($request, $this->companyId);

        $service->createManualPosted($dto);

        return redirect()->route('je.index');
    }

    public function show(Request $request, JournalEntry $journalEntry, JournalEntryQueryService $service): Response|InertiaResponse {
        abort_unless($journalEntry->company_id == $this->companyId, 404);

        return response()->json(
            $service->show($this->companyId, (int) $journalEntry->id),
        );
    }

    public function reverse(
        JournalEntryReverseRequest $request,
        JournalEntry $journalEntry,
        JournalEntryReversalService $service,
    ): Response|InertiaResponse {
        abort_unless($journalEntry->company_id == $this->companyId, 404);

        $dto = JournalEntryReverseDTO::fromRequest($request, $this->companyId, (int) $journalEntry->id);

        $reversal = $service->reverse($dto);

        return response()->json([
            'ok'          => true,
            'reversal_id' => (int) $reversal->id,
        ]);
    }
}
