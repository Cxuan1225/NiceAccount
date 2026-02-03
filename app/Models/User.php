<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable {
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts() : array {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<Company, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function companies(): BelongsToMany {
        return $this->belongsToMany(Company::class)
            ->withPivot([ 'status', 'is_default', 'joined_at' ])
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function activeCompany(): BelongsTo {
        return $this->belongsTo(Company::class, 'active_company_id');
    }

    // helpers
    public function hasCompany(int $companyId) : bool {
        return $this->companies()->where('companies.id', $companyId)->exists();
    }

    public function isSuperAdmin() : bool {
        return $this->hasRole('Super Admin');
    }

}
