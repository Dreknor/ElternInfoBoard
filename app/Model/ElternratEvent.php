<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElternratEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_time',
        'end_time',
        'location',
        'created_by',
        'send_reminder',
        'reminder_hours',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'send_reminder' => 'boolean',
    ];

    /**
     * Get the creator of the event
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all attendees for the event
     */
    public function attendees(): HasMany
    {
        return $this->hasMany(EventAttendee::class, 'event_id');
    }

    /**
     * Get accepted attendees count
     */
    public function acceptedCount(): int
    {
        return $this->attendees()->where('status', 'accepted')->count();
    }

    /**
     * Get declined attendees count
     */
    public function declinedCount(): int
    {
        return $this->attendees()->where('status', 'declined')->count();
    }

    /**
     * Get maybe attendees count
     */
    public function maybeCount(): int
    {
        return $this->attendees()->where('status', 'maybe')->count();
    }

    /**
     * Check if event is in the past
     */
    public function isPast(): bool
    {
        return $this->start_time->isPast();
    }

    /**
     * Check if event is today
     */
    public function isToday(): bool
    {
        return $this->start_time->isToday();
    }

    /**
     * Check if event is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->start_time->isFuture();
    }
}
