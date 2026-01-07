<?php
namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
class JournalEntry extends Model {
    protected $fillable = [
        'company_id',
        'entry_date',
        'reference_no',
        'memo',
        'source_type',
        'source_id',
        'status',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function lines() {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id');
    }
}
