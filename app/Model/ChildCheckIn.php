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
        'checked_in_at',
        'checked_out_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_in' => 'boolean',
            'checked_out' => 'boolean',
            'should_be' => 'boolean',
            'date' => 'date',
            'lock_at' => 'date',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function ($checkIn) {
            // Setze checked_in_at, wenn checked_in von false auf true wechselt
            if ($checkIn->isDirty('checked_in') && $checkIn->checked_in) {
                $checkIn->checked_in_at = now();
            }

            // Setze checked_out_at, wenn checked_out von false auf true wechselt
            if ($checkIn->isDirty('checked_out') && $checkIn->checked_out) {
                $checkIn->checked_out_at = now();
            }

            // Lösche checked_in_at, wenn checked_in auf false gesetzt wird
            if ($checkIn->isDirty('checked_in') && !$checkIn->checked_in) {
                $checkIn->checked_in_at = null;
            }

            // Lösche checked_out_at, wenn checked_out auf false gesetzt wird
            if ($checkIn->isDirty('checked_out') && !$checkIn->checked_out) {
                $checkIn->checked_out_at = null;
            }
        });
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
