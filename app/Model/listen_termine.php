<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\CalendarLinks\Link;

class listen_termine extends Model
{
    protected $table = 'listen_termine';

    protected $fillable = ['listen_id', 'termin', 'comment', 'reserviert_fuer', 'duration'];

    protected $visible = ['listen_id', 'termin', 'comment', 'reserviert_fuer', 'duration'];

    protected $casts = [
        'termin' => 'datetime',
    ];

    public function eingetragenePerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserviert_fuer');
    }

    public function link($Listenname): Link
    {
        return Link::create($Listenname, $this->termin, $this->termin->copy()->addMinutes($this->duration));
    }

    protected function getDurationAttribute($value)
    {
        return ($value != '') ? $value : $this->liste->duration;
    }

    public function liste(): BelongsTo
    {
        return $this->belongsTo(Liste::class, 'listen_id');
    }

    public function scopeUser(Builder $query, $user)
    {
        if ($user != null) {
            return $query->where('reserviert_fuer', $user);
        }
    }
}
