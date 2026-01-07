<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    protected $table = 'audit_trails';

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];
}
