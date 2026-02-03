<?php

namespace App\Models\Accounting;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostingPeriod extends Model
{
    protected $table = 'posting_periods';

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'period_start',
        'period_end',
        'is_locked',
        'locked_by',
        'locked_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<FinancialYear, $this>
     */
    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class, 'financial_year_id');
    }

    /**
     * Scope: period that contains a given date.
     */
    /**
     * @param Builder<PostingPeriod> $query
     * @return Builder<PostingPeriod>
     */
    public function scopeContainingDate(Builder $query, int $companyId, CarbonInterface|string $date): Builder
    {
        $d = $date instanceof CarbonInterface ? $date->toDateString() : (string) $date;

        return $query->where('company_id', $companyId)
            ->whereDate('period_start', '<=', $d)
            ->whereDate('period_end', '>=', $d);
    }

    public function lock(?int $userId = null): void
    {
        $this->is_locked = true;
        $this->locked_by = $userId !== null ? max(0, $userId) : null;
        $this->locked_at = now();
        $this->save();
    }

    public function unlock(): void
    {
        $this->is_locked = false;
        $this->locked_by = null;
        $this->locked_at = null;
        $this->save();
    }
}
