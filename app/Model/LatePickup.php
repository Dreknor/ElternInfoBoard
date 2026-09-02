<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Repräsentiert eine verspätete Abholung eines Kindes (Abholzeit nach der
 * hinterlegten Schickzeit). Muss von berechtigten Nutzern bestätigt oder
 * verworfen werden (z. B. wenn das Austragen vergessen wurde).
 */
class LatePickup extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    public const STATUS_OFFEN = 'offen';

    public const STATUS_BESTAETIGT = 'bestaetigt';

    public const STATUS_VERWORFEN = 'verworfen';

    protected $fillable = [
        'child_id',
        'child_check_in_id',
        'date',
        'weekday',
        'expected_time',
        'picked_up_at',
        'delay_minutes',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_comment',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'expected_time' => 'datetime:H:i',
            'picked_up_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'delay_minutes' => 'integer',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function checkIn(): BelongsTo
    {
        return $this->belongsTo(ChildCheckIn::class, 'child_check_in_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeOffen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OFFEN);
    }

    public function scopeBestaetigt(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_BESTAETIGT);
    }

    public function scopeVerworfen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VERWORFEN);
    }

    public function isOffen(): bool
    {
        return $this->status === self::STATUS_OFFEN;
    }
}
