<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model {
    protected $fillable = [
        'code',
        'name',
        'registration_no',
        'email',
        'phone',
        'address_line1',
        'address_line2',
        'address_line3',
        'city',
        'state',
        'postcode',
        'country',
        'base_currency',
        'currency_precision',
        'timezone',
        'date_format',
        'fy_start_month',
        'lock_date',
        'closing_lock_date',
        'is_active',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'lock_date'          => 'date',
        'closing_lock_date'  => 'date',
        'fy_start_month'     => 'integer',
        'currency_precision' => 'integer',
    ];

    /**
     * @return BelongsToMany<User, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function users(): BelongsToMany {
        return $this->belongsToMany(User::class)
            ->withPivot([ 'status', 'is_default', 'joined_at' ])
            ->withTimestamps();
    }

    /**
     * @return HasMany<User, $this>
     */
    public function primaryUsers(): HasMany {
        return $this->hasMany(User::class);
    }

}
