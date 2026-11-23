<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Einzelne An- oder Abmeldung eines Kindes im Hort (Ereignisprotokoll).
 *
 * @property int $id
 * @property int $child_id
 * @property \Illuminate\Support\Carbon $date
 * @property string $type
 * @property \Illuminate\Support\Carbon $occurred_at
 * @property string $source
 */
class ChildAttendanceEvent extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    public const TYPE_CHECK_IN = 'check_in';

    public const TYPE_CHECK_OUT = 'check_out';

    /** Über die Anwesenheitsliste durch Personal */
    public const SOURCE_STAFF = 'staff';

    /** Automatischer Check-In (Scheduler) */
    public const SOURCE_AUTO = 'auto';

    /** Nachträglich durch Personal erfasst */
    public const SOURCE_MANUAL = 'manual';

    /** Aus den bisherigen Tageszeilen übernommen */
    public const SOURCE_IMPORT = 'import';

    public const SOURCE_LABELS = [
        self::SOURCE_STAFF => 'Anwesenheitsliste',
        self::SOURCE_AUTO => 'automatisch',
        self::SOURCE_MANUAL => 'nachgetragen',
        self::SOURCE_IMPORT => 'importiert',
    ];

    protected $table = 'child_attendance_events';

    protected $fillable = [
        'child_id',
        'date',
        'type',
        'occurred_at',
        'source',
        'user_id',
        'note',
        'original_occurred_at',
        'corrected_by',
        'corrected_at',
        'correction_note',
        'cancelled_by',
        'cancelled_at',
        'cancel_note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'occurred_at' => 'datetime',
            'original_occurred_at' => 'datetime',
            'corrected_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('cancelled_at');
    }

    public function isCheckIn(): bool
    {
        return $this->type === self::TYPE_CHECK_IN;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function isCorrected(): bool
    {
        return $this->original_occurred_at !== null;
    }

    public function sourceLabel(): string
    {
        return self::SOURCE_LABELS[$this->source] ?? $this->source;
    }

    public function typeLabel(): string
    {
        return $this->isCheckIn() ? 'Anmeldung' : 'Abmeldung';
    }
}
