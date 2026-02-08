<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class ChildCheckIn extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'child_id',
        'checked_in',
        'checked_out',
        'should_be',
        'date',
        'lock_at',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'checked_in' => 'boolean',
            'checked_out' => 'boolean',
            'should_be' => 'boolean',
            'date' => 'date',
            'lock_at' => 'date',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /**
     * Scope a query to only include checked-in records.
     */
    public function scopeCheckedIn($query)
    {
        return $query->where('checked_in', true)->where('checked_out', false);
    }
}
