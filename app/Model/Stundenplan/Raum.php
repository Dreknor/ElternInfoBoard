<?php

namespace App\Model\Stundenplan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Raum extends Model
{
    use SoftDeletes;

    protected $table = 'stundenplan_raeume';

    protected $fillable = [
        'kuerzel',
        'name',
        'beschreibung',
        'kapazitaet',
    ];

    /**
     * Einträge in diesem Raum
     */
    public function eintraege(): BelongsToMany
    {
        return $this->belongsToMany(
            Eintrag::class,
            'stundenplan_eintrag_raum',
            'raum_id',
            'eintrag_id'
        )->withTimestamps();
    }
}

