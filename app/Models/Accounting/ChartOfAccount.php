<?php
namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model {
    public const ROLE_OPENING_BALANCE_EQUITY = 'opening_balance_equity';

    protected $fillable = [
        'company_id',
        'account_code',
        'name',
        'type',
        'parent_id',
        'is_active',
    ];

    /**
     * @return BelongsTo<ChartOfAccount, $this>
     */
    public function parent(): BelongsTo {
        return $this->belongsTo(self::class, 'parent_id');
    }
    /**
     * @return HasMany<ChartOfAccount, $this>
     */
    public function children(): HasMany {
        return $this->hasMany(self::class, 'parent_id');
    }
    /**
     * @param Builder<ChartOfAccount> $query
     * @return Builder<ChartOfAccount>
     */
    public function scopeSystemRole(Builder $query, string $role): Builder {
        return $query->where('system_role', $role);
    }
}
