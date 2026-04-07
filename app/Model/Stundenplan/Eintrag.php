<?php

namespace App\Model\Stundenplan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Eintrag extends Model
{
    use SoftDeletes;

    protected $table = 'stundenplan_eintraege';

    protected $fillable = [
        'schuljahr_id',
        'zeitslot_id',
        'fach_id',
        'wochentag',
        'unterrichts_id',
        'bemerkung',
    ];

    /**
     * Schuljahr des Eintrags
     */
    public function schuljahr(): BelongsTo
    {
        return $this->belongsTo(Schuljahr::class, 'schuljahr_id');
    }

    /**
     * Zeitslot des Eintrags
     */
    public function zeitslot(): BelongsTo
    {
        return $this->belongsTo(Zeitslot::class, 'zeitslot_id');
    }

    /**
     * Fach des Eintrags
     */
    public function fach(): BelongsTo
    {
        return $this->belongsTo(Fach::class, 'fach_id');
    }

    /**
     * Klassen des Eintrags (n:m)
     */
    public function klassen(): BelongsToMany
    {
        return $this->belongsToMany(
            Klasse::class,
            'stundenplan_eintrag_klasse',
            'eintrag_id',
            'klasse_id'
        )->withTimestamps();
    }

    /**
     * Lehrer des Eintrags (n:m)
     */
    public function lehrer(): BelongsToMany
    {
        return $this->belongsToMany(
            Lehrer::class,
            'stundenplan_eintrag_lehrer',
            'eintrag_id',
            'lehrer_id'
        )->withTimestamps();
    }

    /**
     * Räume des Eintrags (n:m)
     */
    public function raeume(): BelongsToMany
    {
        return $this->belongsToMany(
            Raum::class,
            'stundenplan_eintrag_raum',
            'eintrag_id',
            'raum_id'
        )->withTimestamps();
    }

    /**
     * Scope für bestimmten Wochentag
     */
    public function scopeForDay($query, int $day)
    {
        return $query->where('wochentag', $day);
    }

    /**
     * Scope für bestimmte Klasse
     */
    public function scopeForKlasse($query, $klasseId)
    {
        return $query->whereHas('klassen', function($q) use ($klasseId) {
            $q->where('stundenplan_klassen.id', $klasseId);
        });
    }

    /**
     * Scope für bestimmten Lehrer
     */
    public function scopeForLehrer($query, $lehrerId)
    {
        return $query->whereHas('lehrer', function($q) use ($lehrerId) {
            $q->where('stundenplan_lehrer.id', $lehrerId);
        });
    }

    /**
     * Scope für bestimmten Raum
     */
    public function scopeForRaum($query, $raumId)
    {
        return $query->whereHas('raeume', function($q) use ($raumId) {
            $q->where('stundenplan_raeume.id', $raumId);
        });
    }
}

