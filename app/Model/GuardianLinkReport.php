<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Meldung einer Bezugsperson, dass eine Kind-Beziehung falsch ist (E3/E6).
 */
class GuardianLinkReport extends Model
{
    protected $fillable = ['child_id', 'user_id', 'reported_by', 'note', 'resolved_at', 'resolved_by'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }
}
