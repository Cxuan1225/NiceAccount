<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function users() {
        return $this->belongsToMany(User::class)
            ->withPivot([ 'status', 'is_default', 'joined_at' ])
            ->withTimestamps();
    }

}

