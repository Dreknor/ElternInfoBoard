<?php

namespace App\Model\Stundenplan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schuljahr extends Model
{
    use SoftDeletes;

    protected $table = 'stundenplan_schuljahre';

    protected $fillable = [
        'name',
        'schulform',
        'beschreibung',
        'datum_von',
        'datum_bis',
        'sw_von',
        'sw_bis',
        'tage_pro_woche',
        'zeitstempel',
        'is_active',
    ];

    protected $casts = [
        'datum_von' => 'date',
        'datum_bis' => 'date',
        'zeitstempel' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Klassen in diesem Schuljahr
     */
    public function klassen(): HasMany
    {
        return $this->hasMany(Klasse::class, 'schuljahr_id');
    }

    /**
     * Zeitslots in diesem Schuljahr
     */
    public function zeitslots(): HasMany
    {
        return $this->hasMany(Zeitslot::class, 'schuljahr_id');
    }

    /**
     * Einträge in diesem Schuljahr
     */
    public function eintraege(): HasMany
    {
        return $this->hasMany(Eintrag::class, 'schuljahr_id');
    }

    /**
     * Scope für aktives Schuljahr
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

