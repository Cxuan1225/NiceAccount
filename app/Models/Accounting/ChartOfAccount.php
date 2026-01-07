<?php
namespace App\Models\Accounting;
use Illuminate\Database\Eloquent\Model;

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

    public function parent() {
        return $this->belongsTo(self::class, 'parent_id');
    }
    public function children() {
        return $this->hasMany(self::class, 'parent_id');
    }
    public function scopeSystemRole($query, string $role) {
        return $query->where('system_role', $role);
    }
}
