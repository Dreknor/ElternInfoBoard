<?php

namespace App\Model\Stundenplan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lehrer extends Model
{
    use SoftDeletes;

    protected $table = 'stundenplan_lehrer';

    protected $fillable = [
        'kuerzel',
        'name',
        'vorname',
        'user_id',
    ];

    /**
     * Einträge dieses Lehrers
     */
    public function eintraege(): BelongsToMany
    {
        return $this->belongsToMany(
            Eintrag::class,
            'stundenplan_eintrag_lehrer',
            'lehrer_id',
            'eintrag_id'
        )->withTimestamps();
    }

    /**
     * Verknüpfter User (optional)
     */
    public function user()
    {
        return $this->belongsTo(\App\Model\User::class, 'user_id');
    }

    /**
     * Vollständiger Name
     */
    public function getFullNameAttribute(): string
    {
        if ($this->vorname && $this->name) {
            return "{$this->vorname} {$this->name}";
        }

        return $this->name ?? $this->kuerzel;
    }
}

