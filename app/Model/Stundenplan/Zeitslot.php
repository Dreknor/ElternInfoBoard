<?php

namespace App\Model\Stundenplan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zeitslot extends Model
{
    protected $table = 'stundenplan_zeitslots';

    protected $fillable = [
        'schuljahr_id',
        'stunde',
        'zeit_von',
        'zeit_bis',
    ];

    protected $casts = [
        'zeit_von' => 'datetime:H:i',
        'zeit_bis' => 'datetime:H:i',
    ];

    /**
     * Schuljahr des Zeitslots
     */
    public function schuljahr(): BelongsTo
    {
        return $this->belongsTo(Schuljahr::class, 'schuljahr_id');
    }

    /**
     * Einträge in diesem Zeitslot
     */
    public function eintraege(): HasMany
    {
        return $this->hasMany(Eintrag::class, 'zeitslot_id');
    }
}

