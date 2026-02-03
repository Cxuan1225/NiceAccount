<?php
namespace App\Services\Accounting\PostingPeriods;

use App\Models\Accounting\PostingPeriod;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class PostingPeriodService {
    /**
     * Assert the entry date belongs to a posting period and it is not locked.
     *
     * $action: 'post', 'unpost', 'void', 'edit', etc (for message)
     * $useLock: only true when you are inside a DB transaction that will WRITE (posting/unposting)
     */
    public static function assertCanPost(
        int $companyId,
        CarbonInterface|\DateTimeInterface|string $entryDate,
        string $action = 'post',
        bool $useLock = false,
    ) : void {
        $date = self::toCarbonDate($entryDate);

        $q = PostingPeriod::query()
            ->where('company_id', $companyId)
            ->where('period_start', '<=', $date->toDateString())
            ->where('period_end', '>=', $date->toDateString());

        // Only lock when you are in a write transaction that needs to prevent race conditions.
        if ($useLock) {
            $q->lockForUpdate();
        }

        $period = $q->first();

        if (!$period) {
            throw ValidationException::withMessages([
                'entry_date' => 'No posting period found for this date. Please create the financial year/period first.',
            ]);
        }

        $label = $date->format('M Y');

        if ((bool) $period->is_locked) {
            throw ValidationException::withMessages([
                'entry_date' => "The posting period for {$label} is locked. You cannot {$action} entries for this date.",
            ]);
        }
    }

    /**
     * Convenience: true/false check.
     */
    public static function canPost(int $companyId, CarbonInterface|\DateTimeInterface|string $entryDate) : bool {
        try {
            self::assertCanPost($companyId, $entryDate, 'post', false);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get the posting period for a date (or null).
     */
    public static function getPeriodForDate(int $companyId, CarbonInterface|\DateTimeInterface|string $entryDate) : ?PostingPeriod {
        $date = self::toCarbonDate($entryDate);

        return PostingPeriod::query()
            ->where('company_id', $companyId)
            ->where('period_start', '<=', $date->toDateString())
            ->where('period_end', '>=', $date->toDateString())
            ->first();
    }

    /**
     * Normalize date inputs.
     */
    private static function toCarbonDate(CarbonInterface|\DateTimeInterface|string $value) : Carbon {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        // string like '2026-01-05'
        return Carbon::parse((string) $value)->startOfDay();
    }
}
