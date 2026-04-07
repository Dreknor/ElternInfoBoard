<?php

namespace App\Model\Stundenplan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Klasse extends Model
{
    use SoftDeletes;

    protected $table = 'stundenplan_klassen';

    protected $fillable = [
        'schuljahr_id',
        'kurzform',
        'name',
    ];

    /**
     * Schuljahr der Klasse
     */
    public function schuljahr(): BelongsTo
    {
        return $this->belongsTo(Schuljahr::class, 'schuljahr_id');
    }

    /**
     * Einträge dieser Klasse
     */
    public function eintraege(): BelongsToMany
    {
        return $this->belongsToMany(
            Eintrag::class,
            'stundenplan_eintrag_klasse',
            'klasse_id',
            'eintrag_id'
        )->withTimestamps();
    }
}

