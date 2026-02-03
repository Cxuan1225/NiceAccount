<?php
namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * @return HasMany<JournalEntryLine, $this>
     */
    public function lines(): HasMany {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id');
    }
}
