<?php
namespace App\Models\Accounting;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    protected $fillable = [
        'company_id',
        'account_code',
        'name',
        'type',
        'parent_id',
        'is_active'
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
