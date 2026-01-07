<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialYear extends Model
{
    protected $table = 'financial_years';

    protected $fillable = [
        'company_id',
        'name',
        'start_date',
        'end_date',
        'is_closed',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_closed' => 'boolean',
    ];

    public function periods(): HasMany
    {
        return $this->hasMany(PostingPeriod::class, 'financial_year_id');
    }

    /**
     * Scope: current FY for a given date.
     */
    public function scopeForDate($query, int $companyId, $date)
    {
        $d = $date instanceof \Carbon\Carbon ? $date->toDateString() : (string) $date;

        return $query->where('company_id', $companyId)
            ->whereDate('start_date', '<=', $d)
            ->whereDate('end_date', '>=', $d);
    }
}
