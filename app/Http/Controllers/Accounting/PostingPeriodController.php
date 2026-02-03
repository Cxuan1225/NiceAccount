<?php

namespace App\Http\Controllers\Accounting;

use App\DTO\Accounting\PostingPeriods\PostingPeriodBulkDTO;
use App\DTO\Accounting\PostingPeriods\PostingPeriodIndexFiltersDTO;
use App\Http\Requests\Accounting\PostingPeriods\PostingPeriodBulkRequest;
use App\Http\Requests\Accounting\PostingPeriods\PostingPeriodIndexRequest;
use App\Models\Accounting\PostingPeriod;
use App\Services\Accounting\PostingPeriods\PostingPeriodLockService;
use App\Services\Accounting\PostingPeriods\PostingPeriodQueryService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class PostingPeriodController extends BaseAccountingController {
    public function index(
        PostingPeriodIndexRequest $request,
        PostingPeriodQueryService $queryService,
    ): Response|InertiaResponse {
        $filters = PostingPeriodIndexFiltersDTO::fromRequest($request, $this->companyId);

        return Inertia::render('Accountings/PostingPeriods/Index', [
            'years'   => $queryService->indexData($filters),
            'filters' => $filters->toFiltersArray(),
        ]);
    }

    public function lock(
        PostingPeriod $period,
        PostingPeriodLockService $lockService,
    ): Response|InertiaResponse {
        abort_unless((int) $period->company_id === (int) $this->companyId, 404);

        $userIdRaw = auth()->id();
        $userId = is_int($userIdRaw) ? $userIdRaw : (is_string($userIdRaw) ? (int) $userIdRaw : null);

        $lockService->lockOne($this->companyId, (int) $period->id, $userId);

        return back()->with('success', 'Locked');
    }

    public function unlock(
        PostingPeriod $period,
        PostingPeriodLockService $lockService,
    ): Response|InertiaResponse {
        abort_unless((int) $period->company_id === (int) $this->companyId, 404);

        $lockService->unlockOne($this->companyId, (int) $period->id);

        return back()->with('success', 'Unlocked');
    }

    public function bulkLock(
        PostingPeriodBulkRequest $request,
        PostingPeriodLockService $lockService,
    ): Response|InertiaResponse {
        $dto = PostingPeriodBulkDTO::fromRequest($request, $this->companyId);

        $lockService->bulkLock($dto);

        return back()->with('success', 'Bulk locked');
    }

    public function bulkUnlock(
        PostingPeriodBulkRequest $request,
        PostingPeriodLockService $lockService,
    ): Response|InertiaResponse {
        $dto = PostingPeriodBulkDTO::fromRequest($request, $this->companyId);

        $lockService->bulkUnlock($dto);

        return back()->with('success', 'Bulk unlocked');
    }
}
